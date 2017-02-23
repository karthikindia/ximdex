<?php
/**
 *  \details &copy; 2011  Open Ximdex Evolution SL [http://www.ximdex.org]
 *
 *  Ximdex a Semantic Content Management System (CMS)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  See the Affero GNU General Public License for more details.
 *  You should have received a copy of the Affero GNU General Public License
 *  version 3 along with Ximdex (see LICENSE file).
 *
 *  If not, visit http://gnu.org/licenses/agpl-3.0.html.
 *
 * @author Ximdex DevTeam <dev@ximdex.com>
 * @version $Revision$
 */


use Ximdex\Deps\DepsManager;
use Ximdex\Logger;
use Ximdex\Models\NodeType;
use Ximdex\Models\PortalVersions;
use Ximdex\Models\Server;
use Ximdex\NodeTypes\ServerNode;
use Ximdex\Runtime\DataFactory;
use Ximdex\Runtime\Db;
use Ximdex\Models\Channel;
use Ximdex\Models\Node;
use Ximdex\Utils\PipelineManager;

//
ModulesManager::file('/inc/model/Batch.class.php', 'ximSYNC');
ModulesManager::file('/inc/model/NodeFrame.class.php', 'ximSYNC');
ModulesManager::file('/inc/model/ServerFrame.class.php', 'ximSYNC');
ModulesManager::file('/inc/model/ChannelFrame.class.php', 'ximSYNC');
ModulesManager::file('/inc/model/NodesToPublish.class.php', 'ximSYNC');
ModulesManager::file('inc/manager/Publication_Log.class.php', 'ximSYNC');
ModulesManager::file('/inc/model/RelFramesPortal.class.php');


/**
 * @brief Handles operations with Batchs.
 *
 *    A Batch is a set of documents which have to be published together for obtain the correct graph of the portal.
 *    This class includes the methods involved in the overall Batch life cycle of Batchs.
 */
class BatchManager
{

    public $idBatchUp;
    public $idBatchDown;
    public $syncStatObj;
    public $otfPublication;

    /**
     * Public constructor
     *
     */
    function __construct()
    {
        $this->setFlag('idBatchUp', NULL);
        $this->setFlag('idBatchDown', NULL);
        $this->setFlag('otfPublication', false);
    }

    /**
     *  Sets the value of any variable.
     */

    /**
     * @param $key string
     * @param $value
     */
    function setFlag($key, $value)
    {
        $this->$key = $value;
    }

    /**
     * Creates the Batchs needed for the publication process.
     * - Add a batch type Up for all processes
     * - Add a batch type Down only if publication end date is determined
     *
     * @param int nodeID
     * @param int up
     * @param int down
     * @param bool otfPublication
     * @param bool force
     */

    function publicate($idNode, $docsToPublish, $docsToPublishVersion, $docsToPublishSubVersion, $up, $down, $physicalServers, $otfPublication, $force, $userId = null)
    {

        $timer = new \Ximdex\Utils\Timer();
        $timer->start();
        $this->setFlag('otfPublication', $otfPublication);
        $node = new Node($idNode);
        $idServer = $node->GetServer();

        Logger::info(_("Publication starts for ") . $node->GetPath() . "($idNode)", 'publication_logger');

        $isOTF = $node->getSimpleBooleanProperty('otf');
        $ancestors = array();
        $unchangedDocs = array();

        foreach ($docsToPublish as $idDoc) {
            $versionToPublish = $docsToPublishVersion[$idDoc];
            $subversionToPublish = $docsToPublishSubVersion[$idDoc];
            $docNode = new Node($idDoc);

            if (!($docNode->get('IdNode') > 0)) {
                Logger::error(_("Unexisting node") . " $idDoc");
                continue;
            }

            // updating the content of afected by enriching

            if ($node->nodeType->get('IsEnriching') == '1') {
                $content = $docNode->GetContent();
                $docNode->SetContent($content);
            }

            if (!$this->isPublishable($idDoc, $up, $down, $force)) {

                $docsToPublish = array_diff($docsToPublish, array($idDoc));
                $unchangedDocs[$idDoc][0][0] = 0;
                continue;
            }

            // Ancestors Batch (linkedsBy, only if nodeID was renamed, change path or first publishing)

            $nodeFrame = new NodeFrame();

            if ($nodeFrame->isTainted($idDoc)) {

                $depsMngr = new DepsManager();
                $sourceNodes = $depsMngr->getByTarget(DepsManager::STRDOC_NODE, $idDoc);

                if (!is_null($sourceNodes)) {
                    $ancestors = array_merge($ancestors, $sourceNodes);
                }
            }

            // We up version if tha current version to publish it is a draft or if the current version is 0.0 and the node is the generator node.
            if ($subversionToPublish != 0 ||
                ($subversionToPublish == 0 && $versionToPublish == 0 && $idDoc == $idNode)) {
                $docsToUpVersion[$idDoc] = $idDoc;
            }
        }

        if (isset($ancestors) && count($ancestors) > 0) {
            $docsToPublish = array_unique(array_merge($docsToPublish, $ancestors));
        }

        // double loop, all the nodes have to be parsed until there were no more to generate

//        do {
//
//            $generated = array();
//            $existDocOtf = $this->_upVersion($docsToUpVersion, $generated);
//            $docsToPublish = array_unique(array_merge($docsToPublish, $generated));
//            $docsToUpVersion = $generated;
//
//        } while (!empty($docsToUpVersion));

        // get portal version

        $portal = new PortalVersions();
        $idPortalVersion = $portal->upPortalVersion($idServer);

        // build batchs

        $docsChunked = array_chunk($docsToPublish, MAX_NUM_NODES_PER_BATCH, true);
        $docsBatch = array();

        $iCount = 1;
        $iTotal = count($docsChunked);

        foreach ($docsChunked as $chunk) {

            Logger::info(sprintf(_("[Generator %s]: Creating bach %s / %s"), $idNode, $iCount, $iTotal), 'publication_logger');

            $partialDocs = $this->buildBatchs($idNode, $up, $chunk, $docsToUpVersion, $docsToPublishVersion, $docsToPublishSubVersion, $idServer, $physicalServers, 0.8, $down, $iCount,
                $iTotal, $idPortalVersion, $userId);
            $docsBatch = array_merge($docsBatch, $partialDocs);
            $iCount++;

            // Update 'chunk' nodes state to 'processed' (state == 2)
            NodesToPublish::setProcessed($chunk, $up);
        }

        $timer->stop();

        Logger::info(_("Publication ended; time for publication") . " = " . $timer->display('m') . _(" minutes"), 'publication_logger');

        return array($docsBatch, $unchangedDocs);
    }

    /**
     * Checks whether the Node can be published.
     *
     * @param int nodeId
     * @param int up
     * @param int down
     * @param bool forcePublication
     * @return bool
     */

    function isPublishable($nodeId, $up, $down, $forcePublication = false)
    {

        $node = new Node($nodeId);
        $depsMngr = new DepsManager();

        if ($node->nodeType->get('IsPublishable') == 0 &&
            !$depsMngr->getByTarget(DepsManager::BULLETIN_XIMLET, $nodeId)
        ) {
            Logger::info(sprintf(_("Node %s belongs to an unpublished nodetype"), $nodeId), 'publication_logger');
            return false;
        }


        if ($node->nodeType->get('IsFolder') == 1) {
            return false;
        }


        if ($forcePublication == true) {
            return true;
        }

        $nodeFrame = new NodeFrame();
        if ($nodeFrame->existsNodeFrame($nodeId, $up, $down)) {
            Logger::info(sprintf(_("Node %s already exists in a NodeFrame"), $nodeId), 'publication_logger');
            return false;
        }

        return true;
    }

    function _upVersion($docs, $generated)
    {
        // Increment version for documents batch
        //finding if there are any otf docs
        if (!is_array($generated)) $generated = array();
        $existDocOtf = false;
        Logger::write(sprintf(_("Incrementing version for %d documents"), count($docs)), 1, 'publication_logger');
        $totalDocs = count($docs);
        $mod = (int)($totalDocs / 10);
        $i = 0;
        foreach ($docs as $value) {
            if (($totalDocs > 50) && ($i % $mod == 0)) {
                Logger::write((int)($i / $totalDocs * 100) . "% " . _("completed"), 1, 'publication_logger');
            }
            $n = new Node($value);
            if ($n->nodeType->get('isGenerator')) {
                $generatedNew = $n->class->generator();
                if (!is_array($generatedNew)) $generatedNew = (array)$generatedNew;
                $generated = array_merge($generatedNew, $generated);
            }

            if (!$existDocOtf) {
                $existDocOtf = ($n->getSimpleBooleanProperty('otf'));
            }
            $dataFactory = new DataFactory($value);
            $dataFactory->AddVersion(true);
            $i++;
        }
        return $existDocOtf;
    }


    function buildBatchs($nodeGenerator, $timeUp, $docsToPublish, $docsToUpVersion, $version, $subversion, $server, $physicalServers, $priority, $timeDown = null,
                         $statStart = 0, $statTotal = 0, $idPortalVersion = 0, $userId = null)
    {

        // If the server is publishing through a channell in which there is not existing documents
        // a batch is created without serverFrames, and it will be deleted at the end of buildFrames method

        $relBatchsServers = array();

        foreach ($physicalServers as $serverId) {
            $batch = new Batch();
            $idBatchDown = null;

            if ($timeDown != 0) {
                $idBatchDown = $batch->create($timeDown, 'Down', $nodeGenerator, 1, null, $idPortalVersion, $userId);
                Logger::info(_('Creating down batch: ') . $timeDown, "mn_logger");
                Logger::info(sprintf(_("[Generator %s]: Creating down batch with id %s"), $nodeGenerator, $idBatchDown), 'publication_logger');
            }

            $batch = new Batch();
            $relBatchsServers[$serverId] = $batch->create(
                $timeUp, 'Up', $nodeGenerator, $priority,
                $idBatchDown, $idPortalVersion, $userId
            );
            Logger::info(_('Creating up batch: ') . $timeUp, "mn_logger");
            Logger::info(sprintf(_("[Generator %s]: Creating up batch with id %s"), $nodeGenerator, $relBatchsServers[$serverId]), 'publication_logger');
        }

        $frames = $this->buildFrames($timeUp, $timeDown, $docsToPublish, $docsToUpVersion, $version, $subversion, $server, $relBatchsServers, $statStart, $statTotal, $nodeGenerator);

        return $frames;
    }

    function buildFrames($up, $down, $docsToPublish, $docsToUpVersion, $versions, $subversions, $serverID, $relBatchsServers, $statStart = 0, $statTotal = 0, $nodeGenerator)
    {

        $docsOk = array();
        $docsNotOk = array();


        $nodeServer = new Node($serverID);


        $totalDocs = count($docsToPublish);
        $mod = (int)($totalDocs / 10);
        $j = 0;

        //creating the frames for each idNode
        foreach ($docsToPublish as $idNode) {

            if (($totalDocs > 20) && ($j % $mod == 0)) {
                Logger::write((int)($j / $totalDocs * 100) . "% " . _("completed"), 1, 'publication_logger');
            }

            $j++;

            $dataFactory = new DataFactory($idNode);
            $node = new Node($idNode);
            // check if current version is 0.0 and not is a node generator. In that case
            $versionZero = (0 == $versions[$idNode] && 0 == $subversions[$idNode]);
            // If node subversion is 0 means that we are publishing a draft.
            // If it is equals to 0 means that we are publishing a version already published in the past.
            // In this case we look for that version.subversion specific because
            // it is possible that exists new drafts that we do not want publish.
            if ($subversions[$idNode] == 0 && !($versionZero && $idNode == $nodeGenerator)) {
                $idVersion = $dataFactory->getVersionId($versions[$idNode], $subversions[$idNode]);
            } else {
                $idVersion = $dataFactory->GetLastVersionId();
            }

            // This var check if a version fof a document 0.0, if node is not the generator
            // and if is structured. In that case we can not publish that document.
            // Therefore if a document is not structured(Css or images, etc)
            // we allow publish it, or if it is the node generator we allow publish it too.
            $notPublish = ($versionZero && $idNode != $nodeGenerator && $node->nodeType->get('IsStructuredDocument') > 0);
            // Check if null $idversion or if $version == 0 and subversion== 0
            if (is_null($idVersion) || $notPublish) {
                $batch = new Batch();
                $batch->batchToLog(null, null, null, null, null, __CLASS__, __FUNCTION__, __FILE__,
                    __LINE__, "INFO", 8, _("No version for node") . " $idNode");
                Logger::warning(sprintf(_("Last version could not be obtained for node %s"), $idNode), 'publication_logger');
                continue;
            }

            //boolean for if any serverframe is created for this nodeframe
            $isServerCreated = false;


            $nodeName = $node->GetNodeName();

            // Blocking node
            $userID = \Ximdex\Utils\Session::get('userID');
            if (is_null($userID)) {
                $userID = 301;
            }
            $node->Block($userID);

            // Creating nodeFrames
            $nf = new NodeFrame();
            $nodeFrames = $nf->find('IdNodeFrame', 'NodeId = %s AND VersionId = %s', array($idNode, $idVersion), MONO);
            if (empty($nodeFrames)) {
                $nodeFrameId = $nf->create($idNode, $nodeName, $idVersion, $up, $down);
            } else {
                $nodeFrameId = $nodeFrames[0];
                $nfr = new NodeFrame($nodeFrameId);
                $nfr->set('IsProcessUp', 0);
                $nfr->set('IsProcessDown', 0);
                $nfr->set('Active', 0);
                $nfr->update();
            }

            if (is_null($nodeFrameId)) {
                $node->unBlock();
                Logger::warning(sprintf(_("A NodeFrame could not be obtained for node %s"), $idNode), 'publication_logger');
                continue;
            }

            if($docsToUpVersion[$idNode]){
                $this->_upVersion(array($docsToUpVersion[$idNode]), NULL);
            }

            $arrayChannels = array();
            if (method_exists($node->class, 'GetChannels')) {
                $arrayChannels = $node->class->GetChannels();
            }
            if (!(count($arrayChannels)) > 0) {
                $arrayChannels[] = 'NULL';
            }

            foreach ($arrayChannels as $channelId) {

                $numFrames = 0;
                $idFrame = NULL;
                $framesBatch = array();

                // Creating channelFrames
                $channel = new Channel($channelId);

                $channelFrame = new ChannelFrame();
                $channelFrameId = $channelFrame->create($channelId, $idNode);
                if (is_null($channelFrameId)) {
                    $node->unBlock();
                    // Deleting nodeFrame previously created
                    $nodeFrame = new NodeFrame($nodeFrameId);
                    $nodeFrame->delete();
                    Logger::warning(sprintf(_("A ChannelFrame could not be obtained for node %s and channel %s"), $idNode, $channelId), 'publication_logger');
                    Logger::warning(sprintf(_("Deleting Nodeframe for node %s"), $idNode), 'publication_logger');
                    continue;
                }

                foreach ($relBatchsServers as $physicalServer => $idBatch) {
                    $idFrame = NULL;

                    //If it is a structured document, check the server and the otf document
                    if ($channelId != 'NULL') {
                        $server = new Server($physicalServer);
                        if ($server->get('otf') == '1') {
                            $isServerOTF = true;
                        } else {
                            $isServerOTF = false;
                        }

                        if (!$isServerOTF && $this->getFlag('otfPublication')) {
                            //server not otf and publication mode is otf
                            Logger::warning(sprintf(_("Server %s is NOT OFT, it will be omitted because of publishing mode is OTF"), $physicalServer), 'publication_logger');
                            break;
                        }

                        if ($isServerOTF && !$this->getFlag('otfPublication')) {
                            //server otf and publication mode no otf
                            Logger::warning(sprintf(_("Server %s is OFT, it will be omitted because of publishing mode is NOT OTF"), $physicalServer), 'publication_logger');
                            break;
                        }

                    }

                    $generatedNodes = array();
                    if ($nodeServer->class->HasChannel($physicalServer, $channelId) || $channelId == 'NULL') {
                        $serverFrame = new ServerFrame();
                        // Creating serverFrames
                        // Generating cache (only if is structured document)
                        if ($channelId > 0) {

                            $data['CHANNEL'] = $channelId;
                            $node = new Node($idNode);
                            $transformer = $node->getProperty('Transformer');
                            $data['TRANSFORMER'] = $transformer[0];
                            $pipeMng = new PipelineManager();

                            if (!$isServerOTF) {

                                $name = $node->GetPublishedNodeName($channelId, true);

                            } else {

                                $name = $nodeName . '.tar';
                            }
                        } else {

                            $name = $node->GetPublishedNodeName($channelId, true);
                        }

                        $path = $node->GetPublishedPath($channelId);
                        $publishLinked = 1;
                        $idFrame = $serverFrame->create($idNode, $physicalServer, $up, $path, $name,
                            $publishLinked, $nodeFrameId, $channelFrameId, $idBatch, $down, $size = 0);
                    }

                    if (is_null($idFrame)) {
                        Logger::warning(sprintf(_("Creation of ServerFrame could not be done: node %s, channel %s, batch %s"), $idNode, $channelId, $physicalServer, $idBatch), 'publication_logger');
                        $docsNotOk[$idNode][$physicalServer][$channelId] = $idFrame;
                    } else {
                        $isServerCreated = true;
                        $numFrames++;

                        //insert into ximnewsframebulletin
                        if ($node->nodeType->get('Module') == 'ximNEWS') {

                            $nodeTypeID = $node->get('IdNodeType');
                            $nodeType = new NodeType($nodeTypeID);

                            if ($nodeType->get('Name') == 'XimNewsBulletinLanguage') {
                                $db = new Db();
                                $sql = "INSERT INTO XimNewsFrameBulletin VALUES ($idFrame, $idNode, 'mail_pending')";
                                $db->Execute($sql);
                                if (!($db->numRows > 0)) {
                                    Logger::info(_("Error inserting ximnewsframebulletin"));
                                }
                            }
                        }
                        $docsOk[$idNode][$physicalServer][$channelId] = $idFrame;
                    }
                }

                if ($numFrames <= 0) {
                    Logger::warning(sprintf(_("Creation of ServerFrame could not be done: node %s, channel %s"), $idNode, $channelId), 'publication_logger');
                    Logger::warning(sprintf(_("ChannelFrame %s will be removed"), $channelFrameId), 'publication_logger');
                    //Deleting the channelFrame previosly created
                    $channelFrame = new ChannelFrame($channelFrameId);
                    $channelFrame->delete();
                }
            }

            if (!$isServerCreated) {
                Logger::warning(sprintf(_("Creation of ServerFrame could not be done: node %s"), $idNode), 'publication_logger');
                Logger::warning(sprintf(_("NodeFrame %s will be eliminated"), $nodeFrameId), 'publication_logger');
                // Deleting nodeFrame previously created
                $nodeFrame = new NodeFrame($nodeFrameId);
                $nodeFrame->delete();
            }
            // Unblocking node
            $node->unBlock();
        }

        // Updating num serverFrames in Batchs
        $allBatchs = array_values($relBatchsServers);
        $tt = implode(',', $allBatchs);
        $result = array();
        $serverFrame = new ServerFrame();
        $result = $serverFrame->find('IdBatchUp, count(IdSync)', "IdBatchUp in ($tt) group by IdBatchUp",
            NULL, MULTI, false);

        Logger::info(sprintf(_("The number of frames in %s batchs will be updated"), count($result)), 'publication_logger');

        if (count($result) > 0) {
            foreach ($result as $dataFrames) {
                $id = $dataFrames[0];
                $ss[] = $dataFrames[0];
                $numFrames = $dataFrames[1];

                Logger::info(sprintf(_("Batch %s uploaded") . ", " . _("total frames %s"), $id, $numFrames), 'publication_logger');

                $batch = new Batch($id);
                $batch->set('ServerFramesTotal', $numFrames);
                $batch->update();

                $idBatchDown = $batch->get('IdBatchDown');

                if ($idBatchDown > 0) {
                    Logger::info(sprintf(_("Batch %s downloaded") . ", " . _("total frames %s"), $idBatchDown, $numFrames), 'publication_logger');
                    $batchDown = new Batch($idBatchDown);
                    $batchDown->set('ServerFramesTotal', $numFrames);
                    $batchDown->update();
                }
            }

            $voidBatchs = array_diff($allBatchs, $ss);
        } else {
            $voidBatchs = $allBatchs;
        }

        // Batchs without serverFrames will be deleted
        if (sizeof($voidBatchs) > 0) {
            foreach ($voidBatchs as $idBatch) {
                Logger::info(sprintf(_("Baths %s will be removed for being empty"), $idBatch), 'publication_logger');

                $batch = new Batch($idBatch);
                $batch->delete();
                $idBatchDown = $batch->get('IdBatchDown');

                if ($idBatchDown > 0) {
                    $batchDown = new Batch($idBatchDown);
                    $batchDown->delete();
                }
            }
        }

        return array('ok' => $docsOk, 'notok' => $docsNotOk);
    }

    /**
     *  Gets the value of any variable.
     * @param string key
     * @return unknown
     */

    function getFlag($key)
    {
        return $this->$key;
    }

    function checkFramesIntegrity()
    {
        // NOTE: See setBatchsActiveOrEnded and getBatchToProcess
        // Ensure that batchs have frames or getBatchToProcess will return the same batch over and over
        $sql = "update Batchs set State = 'NoFrames' where idbatch not in (select distinct idbatchup from ServerFrames) and Batchs.State IN ('InTime','Closing')";
        $db = new Db();
        $db->execute($sql);
        if ($db->numRows > 0) {
            Logger::warning(sprintf(_('Found %s Batchs without Frames, were marked as NoFrames') . ".", $db->numRows));
        }
    }


    /**
     * Starts (Ends) the activity of Batchs.
     * @param int testTime
     */

    function setBatchsActiveOrEnded($testTime = NULL)
    {

        $ended = array();
        $dbObj = new Db();

        // Ending batchs type UP

        $sql = "SELECT ServerFrames.IdBatchUp, SUM(IF(ServerFrames.State='Due2PumpedWithError',1,0)) AS Errors,
			SUM(IF(ServerFrames.State IN ('In','Canceled','Removed','Replaced','Outdated'),1,0)) AS Success,
			SUM(IF(ServerFrames.State IN ('Pumped'),1,0)) AS Pumpeds,
			COUNT(ServerFrames.IdSync) AS Total FROM ServerFrames, Batchs WHERE Batchs.State IN ('InTime','Closing') AND
			Batchs.IdBatch = ServerFrames.IdBatchUp GROUP BY ServerFrames.IdBatchUp HAVING Total = Errors + Success + Pumpeds";
        $dbObj->Query($sql);

        while (!$dbObj->EOF) {

            $idBatch = $dbObj->GetValue("IdBatchUp");
            $errors = $dbObj->GetValue("Errors");
            $success = $dbObj->GetValue("Success");
            $pumpeds = $dbObj->GetValue("Pumpeds");
            $totals = $dbObj->GetValue("Total");

            $batch = new Batch($idBatch);
            $prevState = $batch->get('State');

            $batch->set('ServerFramesSucess', $success);
            $batch->set('ServerFramesError', $errors);

            if ($pumpeds > 0) {
                $batch->set('State', 'Closing');
                $batch->BatchToLog($idBatch, null, null, null, null, __CLASS__, __FUNCTION__, __FILE__,
                    __LINE__, "INFO", 8, sprintf(_("Setting 'Closing' state to %s batch %d UP"), $prevState, $idBatch));
            } else {
                $batch->set('State', 'Ended');
                $batch->BatchToLog($idBatch, null, null, null, null, __CLASS__, __FUNCTION__, __FILE__,
                    __LINE__, "INFO", 8, sprintf(_("Ending %s  batch %d UP"), $prevState, $idBatch));
                Logger::info(_("Ending up batch with id ") . $idBatch, "mn_logger");
            }

            $batch->update();

            if ($batch->get('State') == 'Ended') $this->setPortalRevision($idBatch);

            $dbObj->Next();
        }

        // Ending batchs type DOWN

        $batch = new Batch();
        $batchsDown = $batch->find('IdBatch', "Type = 'Down' AND State = 'InTime' AND Playing = 1", NULL, MONO);

        if (sizeof($batchsDown) > 0) {

            foreach ($batchsDown as $idBatch) {
                $sql = "SELECT SUM(IF(ServerFrames.State='Due2OutWithError',1,0)) AS Errors,
					SUM(IF(ServerFrames.State IN ('Out','Canceled','Removed','Replaced'),1,0)) AS Success,
					COUNT(ServerFrames.IdSync) AS Total FROM ServerFrames, Batchs WHERE
					ServerFrames.IdBatchUp = Batchs.IdBatch AND Batchs.IdBatchDown = $idBatch";

                $dbObj->Query($sql);

                $errors = $dbObj->GetValue("Errors");
                $success = $dbObj->GetValue("Success");
                $totals = $dbObj->GetValue("Total");

                $batchDown = new Batch($idBatch);
                $prevState = $batchDown->get('State');

                if ($totals == 0) {
                    Logger::info(sprintf(_("Batch %d type down without associated batch type up"), $idBatch));

                    $generatorId = $batchDown->get('IdNodeGenerator');

                    $sql = "SELECT SUM(IF(ServerFrames.State='Due2OutWithError',1,0)) AS Errors,
						SUM(IF(ServerFrames.State IN ('Out','Canceled','Removed','Replaced'),1,0)) AS Success,
						COUNT(ServerFrames.IdSync) AS Total FROM NodeFrames, ServerFrames WHERE
							ServerFrames.IdNodeFrame = NodeFrames.IdNodeFrame and NodeFrames.NodeId = $generatorId";

                    $dbObj->Query($sql);

                    $errors = $dbObj->GetValue("Errors");
                    $success = $dbObj->GetValue("Success");
                    $totals = $dbObj->GetValue("Total");
                }

                $batchDown->set('ServerFramesSucess', $success);
                $batchDown->set('ServerFramesError', $errors);

                if ($totals == $errors + $success) {

                    $batchDown->set('State', 'Ended');
                    $batchDown->BatchToLog($idBatch, null, null, null, null, __CLASS__, __FUNCTION__, __FILE__,
                        __LINE__, "INFO", 8, _("Ending " . $prevState . "for batch DOWN $idBatch"));
                    Logger::info(_("Ending down batch with id ") . $idBatch, "mn_logger");
                }

                $batchDown->update();

                if ($batchDown->get('State') == 'Ended') $this->setPortalRevision($idBatch);
            }
        }

        // Batchs to start
        if (!$testTime) {

            $now = time();
        } else {

            $now = $testTime;
        }

        $query = "SELECT IdBatch FROM Batchs WHERE Playing = 1 AND State = 'Waiting' AND TimeOn < $now";
        $dbObj->Query($query);

        $listBatchs = array();
        while (!$dbObj->EOF) {

            $listBatchs[] = $dbObj->GetValue("IdBatch");
            $dbObj->Next();
        }

        foreach ($listBatchs as $batchId) {

            $batch = new Batch($batchId);
            $batch->BatchToLog($batchId, null, null, null, null, __CLASS__, __FUNCTION__, __FILE__,
                __LINE__, "INFO", 8, _("Starting batch") . " $batchId");
            $batch->set('State', 'InTime');
            $batch->update();
        }
    }

    /**
     * Sets the field IdPortalVersion for a Batch.
     * @param int idBatch
     * @return bool
     */

    function setPortalRevision($idBatch)
    {

        $batch = new Batch($idBatch);
        $idPortalVersion = $batch->get('IdPortalVersion');
        $batchType = $batch->get('Type');

        $result = $batch->find('IdBatch', 'State != %s AND IdPortalVersion = %s AND IdBatch != %s AND Type = %s',
            array('State' => 'Ended', 'IdPortalVersion' => $idPortalVersion, 'IdBatch' => $idBatch, 'Type' => $batchType),
            MONO);

        // There still are batchs in this portal version to close

        if (sizeof($result) != 0) {
            return true;
        }

        $portal = new PortalVersions($idPortalVersion);
        $serverId = $portal->get('IdPortal');

        $serverNode = new Node($serverId);
        $physicalServers = $serverNode->class->GetPhysicalServerList(true, ServerNode::ALL_SERVERS);

        if ($batchType == 'Down') {

            // updates portal version for all batchs not ended

            $portal = new PortalVersions();
            $portal->upPortalVersion($serverId);

            $result = $batch->find('IdPortalVersion', 'State != %s AND IdBatch > %s AND Type = %s ORDER BY IdBatch ASC',
                array('State' => 'Ended', 'IdBatch' => $idBatch, 'Type' => 'Up'), MONO);

            $idPortalVersion = $result[0];

            $batch->set('IdPortalVersion', $idPortalVersion);
            $batch->update();

            $db = new Db();
            $db->execute("UPDATE Batchs SET IdPortalVersion = IdPortalVersion + 1 WHERE State != 'Ended'
				AND IdBatch > $idBatch");

        }

        // All the batchs of this portal version are closed

        $serverFrame = new ServerFrame();
        $nodeFrames = $serverFrame->find('DISTINCT(IdNodeFrame)', 'IdServer IN (%s) AND State = %s',
            array('IdServer' => implode(',', $physicalServers), 'State' => 'IN'), MONO);

        if (($nodeFrames != null) && (is_array($nodeFrames))) {
            foreach ($nodeFrames as $nodeFrameId) {
                $relFramePortal = new RelFramesPortal();
                $relFramePortal->addVersion($idPortalVersion, $nodeFrameId);
            }
        } else {
            Logger::error(_("Nodesframes to be added to the portal review do not exist"));
        }

        return true;
    }

    /**
     * Gets the Batch that must be processed.
     */

    function getBatchToProcess()
    {
        $dbObj = new Db();
        $sql = "SELECT IdBatch, Type, IdNodeGenerator, MajorCycle, MinorCycle, ServerFramesTotal FROM Batchs
				WHERE Playing = 1 AND State = 'InTime' AND ServerFramesTotal > 0
				ORDER BY Priority DESC, MajorCycle DESC, MinorCycle DESC, Type = 'Down' DESC LIMIT 1";

        $dbObj->Query($sql);
        $num = $dbObj->numRows;
        if ($num == 0) {
            return false;
        }

        $list = array();
        $list['id'] = $dbObj->GetValue("IdBatch");
        $list['type'] = $dbObj->GetValue("Type");
        $list['nodegenerator'] = $dbObj->GetValue("IdNodeGenerator");
        $list['majorcycle'] = $dbObj->GetValue("MajorCycle");
        $list['minorcycle'] = $dbObj->GetValue("MinorCycle");
        $list['totalserverframes'] = $dbObj->GetValue("ServerFramesTotal");

        return $list;
    }

    /**
     * Sets the number of Scheduler-Cycles consumed by the Batch and updates its priority.
     * @param int idBatch
     * @return bool
     */

    function setCyclesAndPriority($idBatch)
    {

        if (is_null($idBatch)) {
            return false;
        }

        $batch = new Batch($idBatch);
        $majorCycle = $batch->get('MajorCycle');
        $minorCycle = $batch->get('MinorCycle');
        $allFrames = $batch->get('ServerFramesTotal');

        // Unplaying batchs that exceed max num cycles
        if ($majorCycle > MAX_NUM_CICLOS_BATCH) {
            $batch->set('Playing', 0);
            $batch->update();

            $batch->batchToLog(null, null, null, null, null, __CLASS__, __FUNCTION__, __FILE__,
                __LINE__, "INFO", 8, _("Unplaying batch") . " $idBatch");
            return true;
        }

        $sucessFrames = $batch->get('ServerFramesSucess');
        $batchPriority = $batch->get('Priority');


        $cycles = $batch->calcCycles($majorCycle, $minorCycle);

        // Calc priority

        if ($allFrames == 0) {
            $batch->batchToLog(null, null, null, null, null, __CLASS__, __FUNCTION__, __FILE__,
                __LINE__, "INFO", 8, _("Batch without ServerFrames"));

            $batch->set('Playing', 0);
            $batch->update();

            return false;
        }

        $porcentaje = 100 * $sucessFrames / $allFrames;

        if ($porcentaje > 25) {
            $systemPriority = MAX_SYSTEM_PRIORITY;

            $batch->batchToLog(null, null, null, null, null, __CLASS__, __FUNCTION__, __FILE__,
                __LINE__, "INFO", 8, sprintf(_("Up batch %d priority"), $idBatch));
        } else if ($porcentaje < 25) {
            $systemPriority = -MIN_SYSTEM_PRIORITY;

            /*if ($cycles[0] > MAX_NUM_CICLOS_BATCH) {
                $systemPriority = $systemPriority - MIN_SYSTEM_PRIORITY;
            }*/

            $batch->batchToLog(null, null, null, null, null, __CLASS__, __FUNCTION__, __FILE__,
                __LINE__, "INFO", 8, sprintf(_("Down batch %d priority"), $idBatch));
        } else {
            $systemPriority = 0;
        }

        $priority = $batchPriority + $systemPriority;

        if ($priority < MIN_TOTAL_PRIORITY) {
            $priority = (float)MIN_TOTAL_PRIORITY;

        } else if ($priority > MAX_TOTAL_PRIORITY) {
            $priority = (float)MAX_TOTAL_PRIORITY;
        }


        if (is_null($cycles)) {
            $batch->batchToLog(null, null, null, null, null, __CLASS__, __FUNCTION__, __FILE__,
                __LINE__, "ERROR", 8, _("ERROR Calc cycles"));
        } else {

            $batch->set('MajorCycle', $cycles[0]);
            $batch->set('MinorCycle', $cycles[1]);
        }

        $batch->set('Priority', $priority);
        $result = $batch->update();

        if (!($result > 0)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the Batch status is correct.
     * @param array activeAndEnabledServers
     * @return bool
     */

    function checkBatchState($activeAndEnabledServers)
    {

        $dbObj = new Db();
        $batchType = $this->get('Type');
        $totalServerFrames = $this->get('ServerFramesTotal');
        $sucessServerFrames = $this->get('ServerFramesSucess');
        $cycles = $this->get('MajorCycle');

        if ($batchType == 'Up') {
            $batchColumn = 'IdBatchUp';
        } else if ($batchType == 'Down') {
            $batchColumn = 'IdBatchDown';
        } else {
            Logger::info(sprintf(_("ERROR: %s rare type of batch"), $batchType));
            return false;
        }

        $inactives = implode(',', $activeAndEnabledServers);

        $query = "SELECT IdSync FROM ServerFrames, Batchs, Pumpers WHERE ServerFrames.IdBatchUp = Batchs.IdBatch AND " .
            "ServerFrames.PumperId = Pumpers.PumperId AND " .
            "Batchs.$batchColumn = $batchId AND Pumpers.IdServer NOT IN ($inactives)";
        $dbObj->Query($query);

        $numServerFramesFromInactiveServers = $dbObj->numRows;

        if ($totalServerFrames == $numServerFramesFromInactiveServers + $sucessServerFrames) {
            Logger::info(sprintf(_("ERROR: %s rare type of batch"), $batchType));
            $this->set('State', 'Ended');
            $this->update();
        }
    }

    /**
     * Sets the field Playing for all Batchs.
     * @param int playingValue
     */

    function setAllBatchsPlayingOrUnplaying($playingValue)
    {

        $batch = new Batch();
        $dbObj = new Db();

        $sql = "UPDATE Batchs set Playing = '$playingValue'";

        $dbObj->Execute($sql);

        if ($dbObj->numRows > 0) {
            $batch->BatchToLog(null, null, null, null, null, __CLASS__, __FUNCTION__, __FILE__,
                __LINE__, "INFO", 8, $dbObj->numRows . " " . _("Setting batchs to") . ($playingValue == 1) ? " playing" : " unplaying");
        }

    }

    /**
     * Gets the Batch asociated with an active NodeFrame.
     * @param int idNode
     * @param int idServer
     * @return int|null
     */

    function getPublicatedBatchForNode($idNode, $idServer)
    {
        $serverFrame = new ServerFrame();
        $serverFrameId = $serverFrame->getCurrentPublicatedFrame($idNode, $idServer);

        if (!is_null($serverFrameId)) {
            $serverFrame = new ServerFrame($serverFrameId);
            $idBatchUp = $serverFrame->get('IdBatchUp');

            if ($idBatchUp > 0) {
                return $idBatchUp;
            }
        }
        return NULL;
    }

    /**
     * Creates a Batch for remove documents of the publication server (Batch type Down).
     * @param int idBatchUp
     * @param int nodeId
     * @param int serverFramesTotal
     * @return bool
     */

    function buildBatchsFromDeleteNode($idBatchUp, $nodeId, $serverFramesTotal, $userId = null)
    {

        $batch = new Batch();
        $batchDownArray = $batch->getDownBatch($idBatchUp);

        if (isset($batchDownArray) && count($batchDownArray) > 0) {

            // Updating Batch Type Down (if exists) State to Waiting
            $batchDown = new Batch($batchDownArray['IdBatch']);
            $batchDown->set('State', 'Waiting');
            $batchDown->set('ServerFramesTotal', $serverFramesTotal);
            $batchDown->set('ServerFramesSucess', 0);
            $batchDown->set('ServerFramesError', 0);
            $batchDown->update();

        } else {

            // Gets portal version

            $node = new Node($nodeId);
            $serverID = $node->GetServer();

            $portal = new PortalVersions();
            $idPortalVersion = $portal->upPortalVersion($serverID);

            // Creating Batch Type Down if not exist one
            $batchDown = new Batch();
            $idBatchDown = $batchDown->create(mktime(), 'Down', $nodeId, 1, null, $userId);

            // Updating Serverframes info
            $batchDown = new Batch($idBatchDown);
            $batchDown->set('ServerFramesTotal', $serverFramesTotal);
            $batchDown->set('ServerFramesSucess', 0);
            $batchDown->set('ServerFramesError', 0);
            $batchDown->set('PortalVersion', $idPortalVersion);
            $batchDown->update();

        }

        return true;
    }

    /**
     * Starts the publication (again) of a Batch that is finished.
     * @param int idBatchUp
     * @param string frameState
     * @return bool
     */

    function updateBatchFromRepublishAncestors($idBatchUp, $frameState)
    {

        $batch = new Batch($idBatchUp);
        if (!($batch->get('IdBatch') > 0)) {
            $batch->BatchToLog(null, null, null, null, null, __CLASS__, __FUNCTION__, __FILE__,
                __LINE__, "INFO", 8, sprintf(_("Batch %d does not exist"), $idBatchUp));
            return null;
        }
        if ($batch->get('State') == 'Ended') {
            $batch->set('State', 'Waiting');
        }
        if (!strpos($frameState, 'ERROR')) {
            $batch->set('ServerFramesSucess', $batch->get('ServerFramesSucess') - 1);
        } else {
            $batch->set('ServerFramesError', $batch->get('ServerFramesError') - 1);
        }
        $batch->update();

        return null;
    }

    /**
     * Gets all batchs that must be processed (used by MPM).
     * @return array
     */

    function getAllBatchToProcess()
    {

        $dbObj = new Db();
        $sql = "SELECT IdBatch, Type, IdNodeGenerator, MajorCycle, MinorCycle, ServerFramesTotal FROM Batchs
				WHERE Playing = 1 AND State = 'InTime' AND ServerFramesTotal > 0
				ORDER BY Priority DESC, MajorCycle DESC, MinorCycle DESC, Type = 'Down'";
        $dbObj->Query($sql);

        if ($dbObj->numRows > 0) {
            $batchs = array();
            $i = 0;
            while (!$dbObj->EOF) {
                $list = array();
                $list['id'] = $dbObj->GetValue("IdBatch");
                $list['type'] = $dbObj->GetValue("Type");
                $list['nodegenerator'] = $dbObj->GetValue("IdNodeGenerator");
                $list['majorcycle'] = $dbObj->GetValue("MajorCycle");
                $list['minorcycle'] = $dbObj->GetValue("MinorCycle");
                $list['totalserverframes'] = $dbObj->GetValue("ServerFramesTotal");
                $batchs[$i] = $list;
                $i++;
                $dbObj->Next();
            }
        } else {
            return false;
        }
        return $batchs;
    }

}
