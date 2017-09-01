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
use Ximdex\Models\Node;
use Ximdex\NodeTypes\FileNode;
use Ximdex\Runtime\App;
use Ximdex\Utils\FsUtils;
use Ximdex\Logger;


if (!defined('XIMDEX_ROOT_PATH')) {
    define('XIMDEX_ROOT_PATH', realpath(dirname(__FILE__) . '/../../'));
}


class xsltnode extends FileNode
{

    private $xsltOldName = ""; //String;
    public $messages;

    public function __construct(&$node)
    {

        if (is_object($node))
            $this->parent = $node;
        else if (is_numeric($node) || $node == null)
            $this->parent = new Node($node, false);
        $this->nodeID = $this->parent->get('IdNode');
        $this->dbObj = new DB();
        $this->nodeType = &$this->parent->nodeType;
        $this->messages = new \Ximdex\Utils\Messages();
        $this->xsltOldName = $this->parent->get("Name");
    }


    public function CreateNode($xsltName = null, $parentID = null, $nodeTypeID = null, $stateID = null, $ptdSourcePath = NULL)
    {

        $xslSourcePath = NULL;
        //	if (is_null($ptdSourcePath))  return;
        if ($ptdSourcePath != null) {

            // Saving xslt content

            $xslContent = FsUtils::file_get_contents($ptdSourcePath);
            $xslContent = $this->sanitizeContent($xslContent);

            $xslSourcePath = App::getValue('AppRoot') . App::getValue('TempRoot') . '/' . $parentID . $xsltName;

            if (!FsUtils::file_put_contents($xslSourcePath, $xslContent)) {
                Logger::error("Error saving xslt file");
                $this->messages->add('Error saving xslt file: ' . $parentID . $xsltName, MSG_TYPE_ERROR);
                return false;
            }
        }
        parent::CreateNode($xsltName, $parentID, $nodeTypeID, $stateID, $xslSourcePath);

        // Checks if exists template_include.xsl node
        if ($xsltName != 'docxap.xsl') {
            if ($this->setIncludeContent($xsltName, $parentID, $nodeTypeID, $stateID) === false)
                return false;
        }

        // Checks if exists docxap.xsl node

        $node = new Node($this->nodeID);
        $ximPtdNode = new Node($parentID);

        $project = new Node($node->GetProject());
        $idXimptdProject = $project->GetChildByName('templates');

        $ptdProject = new Node($idXimptdProject);
        $idDocxapProject = $ptdProject->GetChildByName('docxap.xsl');

        if ($xsltName != 'docxap.xsl' && $ximPtdNode->get('IdParent') != $node->GetProject()
            && !($ximPtdNode->GetChildByName('docxap.xsl') > 0) && ($idDocxapProject > 0)
        ) {

            // get and copy project docxap

            $docxapProject = new Node($idDocxapProject);
            $docxapContent = $docxapProject->GetContent();

            $docxapProjectPath = XIMDEX_ROOT_PATH . App::getValue('TempRoot') . '/docxap.xsl';

            $dummyXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
				<dext:root xmlns:dext=\"http://www.ximdex.com\" xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\">
				<xsl:dummy />
				</dext:root>";

            if (FsUtils::file_put_contents($docxapProjectPath, $dummyXml) === false)
            {
                $this->messages->add('Error copying project docxap.xls file', MSG_TYPE_ERROR);
                return false;
            }

            $docxapNode = new Node();
            $id = $docxapNode->CreateNode('docxap.xsl', $parentID, $nodeTypeID, $stateID, $docxapProjectPath);

            if ($id > 0) {
                $docxapNode = new Node($id);
                if ($docxapNode->SetContent($docxapContent) === false)
                {
                    $this->messages->mergeMessages($docxapNode->messages);
                    return false;
                }
            }
            else
            {
                $this->messages->mergeMessages($docxapNode->messages);
                return false;
            }
        }
        return true;
    }


    /**
     * Make a xsl:include line and call to inserts on inclusion files
     * @param string $fileName
     * @param integer $parentId
     * @param integer $nodeTypeId
     * @param integer $stateID
     * @return boolean|true
     */
    private function setIncludeContent($fileName, $parentId, $nodeTypeId, $stateID)
    {
        if ($fileName == 'docxap.xsl')
        {
            Logger::info('docxap.xsl can\'t be include in templates_include.xsl file');
            return true;
        }
        if ($fileName != "templates_include.xsl") {
            $node = new Node($this->nodeID);
            $projectId = $node->GetProject();

            $ximptd = new Node($parentId);
            $idProject = $node->GetProject();

            $ptdFolder = App::getValue("TemplatesDirName");

            if ($ximptd->get('IdParent') == $projectId) {

                // Making include in project (modify includes from project and its sections)
                $this->writeIncludeFile($fileName, $projectId, $nodeTypeId, $stateID);
                
            } else {

                // Making include only in section ximptd
                $sectionId = $node->GetSection();
                $section = new Node($sectionId);
                return $this->writeIncludeFile($fileName, $sectionId, $nodeTypeId, $stateID);
            }

        } else {
            Logger::info("templates_include.xsl wont be include in itself.");
        }
        return true;
    }

    /**
     * Insert xsl:include line in inclusion
     * @param string $templateName
     * @param integer $sectionId
     * @param integer $nodeTypeID
     * @param integer $stateID
     * @return boolean
     */
    private function writeIncludeFile($templateName, $sectionId, $nodeTypeID, $stateID)
    {
        $section = new Node($sectionId);
        $ximPtdId = $section->GetChildByName('templates');
        if ($ximPtdId === false)
        {
            $this->messages->add('Can\'t get the Templates folder to include the template ' . $templateName, MSG_TYPE_ERROR);
            return false;
        }

        $parent = new Node($ximPtdId);
        $includeId = $parent->GetChildByName('templates_include.xsl');

        if (!($includeId > 0)) {

            $xslSourcePath = App::getValue('AppRoot') . App::getValue('TempRoot') . '/templates_include.xsl';

            // Creating include file

            Logger::info("Creating unexisting include xslt file at folder $ximPtdId");

            $includeContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $includeContent .= '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' . "\n";
            $includeContent .= "\t<xsl:include href=\"$templateName\"/>\n";
			$includeContent .= '</xsl:stylesheet>';
            /*
            $arrayContent = explode("\n", $includeContent);
            $includeContent = implode("\n", array_unique($arrayContent));
            */
            $dummyXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
				<dext:root xmlns:dext=\"http://www.ximdex.com\" xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\">
				<xsl:dummy />
				</dext:root>";

            if (!FsUtils::file_put_contents($xslSourcePath, $dummyXml)) {
                Logger::error("Error saving templates_include.xsl file");
                $this->messages->add('Error saving templates_include.xsl file', MSG_TYPE_ERROR);
                return false;
            }

            $incNode = new Node();
            $id = $incNode->CreateNode('templates_include.xsl', $ximPtdId, $nodeTypeID, $stateID, $xslSourcePath);

            if ($id > 0) {
                $incNode = new Node($id);
                $incNode->SetContent($includeContent);
            }
            
            //it is not necesary to create a docxap file in a project based in a theme
            if (!isset($GLOBALS['fromTheme']) or !$GLOBALS['fromTheme'])
            {
                //If there is not a docxap.xsl file in the project/templates folder, create a new one
                $res = $this->create_project_docxap_file();
                if ($res === false)
                {
                    Logger::fatal('The project docxap XSL template could not been created');
                    return false;
                }
                //check if the docxap project already exists (where are not in a new section)
                if ($res === null)
                {
                    //the new templates_include reference must be added in docxap file, and the existing parent ones
                    $idDocxap = $parent->GetChildByName('docxap.xsl');
                    if ($idDocxap)
                    {
                        $docxapNode = new Node($idDocxap);
                        $docxapContent = $docxapNode->GetContent();
                        if (!$docxapContent)
                        {
                            $this->messages->add('Docxap XML content is empty', MSG_TYPE_ERROR);
                            return false;
                        }
                        $domDocument = new DOMDocument();
                        $domDocument->formatOutput = true;
                        $domDocument->preserveWhiteSpace = false;
                        if (@$domDocument->loadXML($docxapContent) === false)
                        {
                            $this->messages->add('Can\'t load the docxap XML content', MSG_TYPE_ERROR);
                            return false;
                        }
                        
                        /*
                        The new tag will be include before the xsl:template tag, and will looks like this
                            <xsl:include href="http://server/data/nodes/Project/Server/NewSection/templates/templates_include.xsl" />
                        Find in the xsl:stylesheet root, the xsl:template node
                        */
                        $xPath = new DomXPath($domDocument);
                        $docxapRoot = $xPath->query('//xsl:stylesheet');
                        if (!$docxapRoot->length)
                        {
                            $this->messages->add('Can\'t find the xsl:stylesheet element in the docxap XML content', MSG_TYPE_ERROR);
                            return false;
                        }
                        $templateNodes = $xPath->query('//xsl:stylesheet/xsl:include');
                        if (!$templateNodes->length)
                        {
                            $this->messages->add('Can\'t find the xsl:template element in the docxap XML content', MSG_TYPE_ERROR);
                            return false;
                        }
                        
                        //get the current project node
                        $project = new Node($docxapNode->GetProject());
                        
                        //for each templates include file existing in the parent nodes (sections and server), insert the reference in the new docxap file
                        do
                        {
                            if ($section->GetNodeType() != Ximdex\Services\NodeType::SECTION 
                                        and $section->GetNodeType() != Ximdex\Services\NodeType::SERVER)
                                break;
                            
                            //check if the templates folder of this section or server contains a templates_include.xsl node 
                            $idTemplatesNode = $section->GetChildByName('templates');
                            if (!$idTemplatesNode)
                                continue;
                            $templatesNode = new Node($idTemplatesNode);
                            $idTemplatesInclude = $templatesNode->GetChildByName('templates_include.xsl');
                            if (!$idTemplatesInclude)
                                continue;
                            
                            //get the path to the current section node
                            $tempPath = $section->GetRelativePath($project->GetID());
                            
                            //set the URL to the tmplates include in the element
                            $templatesIncludeURL = App::getValue('UrlRoot') . App::getValue('NodeRoot') . $tempPath . '/templates/templates_include.xsl';

                            //generate the include tag with its href value
                            $domElement = $domDocument->createElement('xsl:include');
                            $domAttribute = $domDocument->createAttribute('href');
                            
                            //add the element
                            $domAttribute->value = $templatesIncludeURL;
                            $domElement->appendChild($domAttribute);
                            $docxapRoot->item(0)->insertBefore($domElement, $templateNodes->item(0));
                            
                            //load the previous section if exists
                        }
                        while ($section = new Node($section->GetParent()));
                        
                        //saves the new XML modified 
                        $docxapContent = $domDocument->saveXML();
                        if ($docxapContent === false)
                        {
                            $this->messages->add('Can\'t save the project docxap XML content', MSG_TYPE_ERROR);
                            return false;
                        }
                        
                        //save the content
                        if ($docxapNode->SetContent($docxapContent) === false)
                        {
                            $this->messages->mergeMessages($docxapNode->messages);
                            return false;
                        }
                        if ($docxapNode->RenderizeNode() === false)
                        {
                            $this->messages->mergeMessages($docxapNode->messages);
                            return false;
                        }
                        Logger::info('New files templates_include.xls have been included in the section docxap XSL document');
                    }
                }
            }

        } else {

            $includeNode = new Node($includeId);
            $includeContent = $includeNode->getContent();
            $dom = new DOMDocument();
            $dom->formatOutput = true;
            $dom->preserveWhiteSpace = false;
            $dom->validateOnParse = true;
            if (!@$dom->loadXML($includeContent))
            {
                $this->messages->add('File templates_include.xsl for section ' . $sectionId . ' has errors', MSG_TYPE_ERROR);
                return false;
            }
            
            //check if there is a template with that name
            $xPath = new DOMXPath($dom);
            $includeTag = $xPath->query("/xsl:stylesheet/xsl:include[@href='$templateName']");
            if ($templateIncludeTag)
            {
                //template already exists
                return true;
            }
            $rootTag = $xPath->query('/xsl:stylesheet');
            
            //generate the include tag with its href value
            $domElement = $dom->createElement('xsl:include');
            $domAttribute = $dom->createAttribute('href');
            
            //add the element
            $domAttribute->value = $templateName;
            $domElement->appendChild($domAttribute);
            $rootTag->item(0)->appendChild($domElement);
            
            //save XSL content in the node
            $includeContent = $dom->saveXML();
            if ($includeContent === false)
            {
                $error = \Ximdex\Error::error_message();
                $this->messages->add($error, MSG_TYPE_ERROR);
                return false;
            }
            $includeNode->setContent($includeContent);
        }
        return true;
    }

    /**
     * Rename a node
     * @param string $newName
     * @return boolean
     */
    public function RenameNode($newName = NULL)
    {
        if (null == $newName) return false;

        $nodeTypeId = $this->parent->get('IdNodeType');
        $projectId = $this->parent->GetProject();
        $parentId = $this->parent->get('IdParent');
        $sectionId = $this->parent->getSection();
        $oldName = explode(".", $this->xsltOldName);
        $newName = explode(".", $newName);
        if (count($newName) != 2)
        {
            $this->messages->add('The file extension is necessary', MSG_TYPE_ERROR);
            return false;
        }
        if ($this->xsltOldName) {
            $templateName = $this->xsltOldName;
            if ($this->removeIncludeFile($templateName, $sectionId, $nodeTypeId) === false)
                return false;
            if ($this->removeIncludeFile($templateName, $projectId, $nodeTypeId) === false)
                return false;
        }
        //open the file and make the replacement inside
        $tpl = new Node($this->nodeID);
        $rpl1 = 'name="' . $oldName[0];
        $rpl2 = 'name="' . $newName[0];
        $new_content = str_replace($rpl1, $rpl2, $tpl->GetContent());
        $rpl1 = 'match="' . $oldName[0];
        $rpl2 = 'match="' . $newName[0];
        $new_content = str_replace($rpl1, $rpl2, $new_content);
        $tpl->SetContent($new_content);
        
        if ($this->setIncludeContent($newName[0] . "." . $newName[1], $parentId, $nodeTypeId, null) === false)
            return false;
        
        return true;
    }

    /**
     * Delete a node
     */
    public function deleteNode()
    {

        // Deletes dependencies in rel tables
        $nodeId = $this->nodeID;
        $node = new Node($nodeId);
        $sectionId = $this->parent->getSection();
        $nodeTypeId = $this->parent->get('IdNodeType');
        $templateName = $this->parent->get('Name');
        $this->removeIncludeFile($templateName, $sectionId, $nodeTypeId);

        $projectId = $node->GetProject();
        $this->removeIncludeFile($templateName, $projectId, $nodeTypeId);

        $depsMngr = new DepsManager();
        $depsMngr->deleteByTarget(DepsManager::STRDOC_TEMPLATE, $this->parent->get('IdNode'));

        Logger::info('Xslt dependencies deleted');
    }

    /**
     * Remove from template_includes $templateName occurrences
     * @param string $templateName
     * @param integer $sectionId
     * @param integer $nodeTypeId
     * @return boolean
     */
    private function removeIncludeFile($templateName, $sectionId, $nodeTypeId)
    {
        $section = new Node($sectionId);
        $ximPtdId = $section->GetChildByName('templates');

        $parent = new Node($ximPtdId);
        $includeId = $parent->GetChildByName('templates_include.xsl');


        if ($includeId > 0) {

            $includeNode = new Node($includeId);
            $includeContent = $includeNode->getContent();
            $dom = new DOMDocument();
            $dom->formatOutput = true;
            $dom->preserveWhiteSpace = false;
            $dom->validateOnParse = true;
            if (!@$dom->loadXML($includeContent))
            {
                $this->messages->add('File templates_include.xsl for section ' . $sectionId . ' has errors', MSG_TYPE_ERROR);
                return false;
            }
            
            //check if there is a template with that name
            $xPath = new DOMXPath($dom);
            $includeTag = $xPath->query("/xsl:stylesheet/xsl:include[@href='$templateName']");
            if (!$includeTag->length)
            {
                //template does not exists
                //$this->messages->add('The template named ' . $templateName . ' does not exists in templates_include.xsl', MSG_TYPE_ERROR);
                return true;
            }
            
            //remove the specified include element
            $includes = $dom->getElementsByTagName('stylesheet');
            $includes->item(0)->removeChild($includeTag->item(0));
            
            //save XSL content in the node
            $includeContent = $dom->saveXML();
            if ($includeContent === false)
            {
                $error = \Ximdex\Error::error_message();
                $this->messages->add($error, MSG_TYPE_ERROR);
                return false;
            }
            $includeNode->setContent($includeContent);
        }
        return true;
    }

    public function SetContent($content, $commitNode = NULL, Node $node = null)
    {
        //checking the valid XML of the given content
        $domDoc = new DOMDocument();
        $domDoc->formatOutput = true;
        $domDoc->preserveWhiteSpace = false;
        if (@$domDoc->loadXML($content) === false)
        {
            //we don't allow to save an invalid XML
            $this->messages->add('The XML document is not valid. Changes have not been saved', MSG_TYPE_ERROR);
            if (isset($GLOBALS['InBatchProcess']))
            {
                if ($node and $node->getDescription())
                    Logger::error('Invalid XML for node: ' . $node->getDescription());
                else
                    Logger::error('Invalid XML to set content operation');
            }
            $error = \Ximdex\Error::error_message('DOMDocument::loadXML(): ');
            if ($error)
                $this->messages->add($error, MSG_TYPE_WARNING);
            return false;
        }
        
        //validating of the correct XSL document in the correct system path (only if node is given)
        if ($node)
        {
            $xsltprocessor = new XSLTProcessor();
            //replace the includes templates for its implicit reference templates
            $res = $this->include_unique_templates($content, $node);
            if ($res === false)
            {
                $this->messages->add('The XSL document have errors and it have not been saved', MSG_TYPE_ERROR);
                return false;
            }
            $dom = new DOMDocument();
            $dom->loadXML($res);
            $project = new Node($node->GetProject());
            $dom->documentURI = App::getValue('AppRoot') . App::getValue('NodeRoot') . $node->GetRelativePath($project->GetID());
            if (@$xsltprocessor->importStyleSheet($dom) === false)
            {
                //we don't allow to save an invalid XSL
                $this->messages->add('The XSL document (or its inclusions) has errors. Changes have not been saved', MSG_TYPE_ERROR);
                if (isset($GLOBALS['InBatchProcess']))
                {
                    if ($node and $node->getDescription())
                        Logger::error('Invalid XSL for node: ' . $node->getDescription());
                    else
                        Logger::error('Invalid XSL to set content operation');
                }
                $error = \Ximdex\Error::error_message('XSLTProcessor::importStylesheet(): ');
                if ($error)
                    $this->messages->add($error, MSG_TYPE_WARNING);
                return false;
            }
        }
        $content = $domDoc->saveXML();
        
        $content = $this->sanitizeContent($content);
        if ($content === false)
            return false;
        
        if (parent::SetContent($content, $commitNode, $node) === false)
            return false;
    }

    private function sanitizeContent($content)
    {
        if (empty($content)) {
            Logger::info('It have been created or edited a document with empty content');
            return $content;
        }
        
        $xsldom = new DOMDocument();
        $xsldom->formatOutput = true;
        $xsldom->preserveWhiteSpace = false;
        $xsldom->loadXML($content);
        $xpath = new DOMXPath($xsldom);

        $nodelist = $xpath->query('//xsl:text');
        $count = $nodelist->length;
        for ($i = 0; $i < $count; $i++) {
            $textnode = $nodelist->item($i);
            // Split CDATA sections if contains attributes references
            $nodes = $this->splitCData($textnode, $xsldom);
            // If splitCData returns only one node there is nothing to change, it's the same node
            if (count($nodes) > 1) {
                $parent = $textnode->parentNode;
                foreach ($nodes as $node) {
                    $parent->insertBefore($node, $textnode);
                }
                $parent->removeChild($textnode);
            }
        }

        $content = $xsldom->saveXML();
        return $content;
    }

    private function splitCData($node, &$xsldom)
    {

        $nodevalue = $node->nodeValue;

        // Split CDATA sections if contains attributes references
        $ret = preg_match_all('/"{@([^}]+)}"/', $nodevalue, $matches);

        if (!$ret) {
            return array($node);
        } else {

            $matches = array_unique($matches[1]);
            $attribute = $matches[0];

            $attrvalue = "@$attribute";
            $sep = '{' . $attrvalue . '}';
            $tokens = explode($sep, $nodevalue);
            $arrCD = array();

            $count = count($tokens);
            for ($i = 0; $i < $count; $i++) {

                $token = $tokens[$i];
                $textnode = $xsldom->createElement('xsl:text');
                $textnode->setAttribute('disable-output-escaping', 'yes');
                $textnode->appendChild($xsldom->createCDATASection($token));

                $arrCD = array_merge($arrCD, (array)$this->splitCData($textnode, $xsldom));

                if ($i < ($count - 1)) {
                    $valueof = $xsldom->createElement('xsl:value-of');
                    $valueof->setAttribute('select', $attrvalue);
                    $arrCD[] = $valueof;
                }
            }

            return $arrCD;
        }
    }

    /**
     *    Get the documents that must be publish when the template is published
     * @param array $params
     * @return array
     */
    public function getPublishabledDeps($params)
    {
        $depsMngr = new DepsManager();
        return $depsMngr->getByTarget(DepsManager::STRDOC_TEMPLATE, $this->parent->get('IdNode'));
    }
    
    /**
     * Create a new basic docxap XSLT file for the project if it's not exists
     * @param null|int $sectionId
     * @return boolean|NULL|int
     */
    private function create_project_docxap_file()
    {
        //obtain the project node
        $node = new Node($this->nodeID);
        $project = new Node($node->GetProject());
        
        //obtain the project templates node
        $idXimptdProject = $project->GetChildByName('templates');
        $ptdProject = new Node($idXimptdProject);
        
        //obtain the ID for an existant docaxp file yet
        $idDocxapProject = $ptdProject->GetChildByName('docxap.xsl');
        if ($idDocxapProject)
            return null;
            //return $idDocxapProject;
        
        //generation of the file docxap.xsl with project name inside
        $xslSourcePath = App::getValue('AppRoot') . App::getValue('TempRoot') . '/docxap.xsl';
        Logger::info('Creating unexisting docxap XSLT file in ' . $xslSourcePath);
        $docxapTemplate = App::getValue('AppRoot') . '/xmd/xslt/docxap.xsl.template';
        $content = FsUtils::file_get_contents($docxapTemplate);
        if (!$content)
            return false;
        
        //make sure that the path to templates is in the correct place of the project docxap templates folder
        $content = str_replace('##URL_ROOT##', App::getValue("UrlRoot"), $content);
        $content = str_replace('##PROJECT_NAME##', $project->GetNodeName(), $content);
		if (!FsUtils::file_put_contents($xslSourcePath, $content))
		    return false;
		
		//obtain the ID for XSL templates node type
		$nodeTypeID = Ximdex\Services\NodeType::XSL_TEMPLATE;
		
		//create the node for the generated file
		$node = new Node();
		$idDocxapProject = $node->CreateNode('docxap.xsl', $idXimptdProject, $nodeTypeID, null, $xslSourcePath);	
		if (!$idDocxapProject)
		{
		    Logger::error('Error creating the node for project docxap template');
		    return false;
		}
		
		Logger::info('Project docxap.xsl node generated');
		
		//return the ID for the new project docxap template node
		return $idDocxapProject;
    }
    
    /**
     * Includes the content of all documents referenced by xsl:include tags
     * For each one, it change the relative location of the templates references to the URL of the project corresponding node
     * The purpose is remove duplicated templates and maintain only the one referenced by the node given
     * @param string $content
     * @param Node $node
     * @return string|boolean|mixed
     */
    public function include_unique_templates($content, Node $node)
    {
        if ($node->GetNodeName() != 'docxap.xsl')
            return $content;
            
        //Load de XML document from the XSL content given
        $dom = new DOMDocument();
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($content);
        
        //load the root node
        $xPath = new \DomXPath($dom);
        $docxapRoot = $xPath->query('//xsl:stylesheet');
        if (!$docxapRoot->length)
        {
            $error = 'Can\'t find the xsl:stylesheet element in the docxap XML content';
            if (isset($GLOBALS['InBatchProcess']))
                Logger::error($error . ' for node: ' . $node->getDescription());
            else
                $this->messages->add($error, MSG_TYPE_WARNING);
            return false;
        }
        
        //load de templates inclusions
        $includes = $xPath->query('//xsl:stylesheet/xsl:include');
        if (!$includes->length)
        {
            //there isn't templates includes
            return $content;
        }
        
        $templatesElement = $xPath->query('//xsl:stylesheet/xsl:template');
        if (!$templatesElement->length)
        {
            //there isn't template tag
            $error = 'Can\'t find the xsl:template element in the docxap XML content';
            if (isset($GLOBALS['InBatchProcess']))
                Logger::error($error . ' for node: ' . $node->getDescription());
            else
                $this->messages->add($error, MSG_TYPE_WARNING);
            return false;
        }
        
        //load the templates_include references from the docxap file into an array
        $templatesInclude = array();
        foreach ($includes as $templateInclude)
        {
            $templatesInclude[$templateInclude->getAttribute('href')] = $templateInclude;
        }
        
        //the templates to include will be storage in an array
        $templates = array();
        $project = new Node($node->GetProject());
        $xslDom = new \DOMDocument();
        
        //load the parent nodes if exist
        $parent = new Node($node->GetID());
        while ($parent = new Node($parent->GetParent()))
        {
            //if the parent node is the projects section, stop the search
            if ($parent->GetNodeType() == Ximdex\Services\NodeType::PROJECTS)
                break;
                
            //if the parent node is not a section, a server or a project, continues to the next parent node
            if ($parent->GetNodeType() != Ximdex\Services\NodeType::SECTION and $parent->GetNodeType() != Ximdex\Services\NodeType::SERVER 
                    and $parent->GetNodeType() != Ximdex\Services\NodeType::PROJECT)
                continue;
                
            //check if the templates folder of this node contains a folder templates with a templates_include.xsl node
            $idTemplatesNode = $parent->GetChildByName('templates');
            if (!$idTemplatesNode)
                continue;
            $templatesNode = new Node($idTemplatesNode);
            $idTemplatesInclude = $templatesNode->GetChildByName('templates_include.xsl');
            if (!$idTemplatesInclude)
                continue;
                
            //get the path to the current node
            $tempPath = $parent->GetRelativePath($project->GetID());
            
            //set the URL to the templates include in the element
            $templatesIncludeURL = App::getValue('UrlRoot') . App::getValue('NodeRoot') . $tempPath . '/templates/templates_include.xsl';
            
            //if the template inclusion is not referenced in the docxap file, continue to the next parent node
            if (!isset($templatesInclude[$templatesIncludeURL]))
                continue;
                
            //obtain the URL to the templates includes and check the file name
            $includeURL = $templatesInclude[$templatesIncludeURL]->getAttribute('href');
            $res = FsUtils::get_url_file($includeURL);
            if (!$res)
            {
                $error = 'The templates include file has not file in: ' . $includeURL;
                if (isset($GLOBALS['InBatchProcess']))
                    Logger::error($error . ' for node: ' . $node->getDescription());
                else
                    $this->messages->add($error, MSG_TYPE_WARNING);
                return false;
            }
            if ($res != 'templates_include.xsl')
                continue;
                
            //load the template related to the URL obtained
            if (!@$xslDom->load($includeURL))
            {
                $error = 'Can\'t load the templates include: ' . $includeURL;
                if (isset($GLOBALS['InBatchProcess']))
                    Logger::error($error . ' for node: ' . $node->getDescription());
                else
                    $this->messages->add($error, MSG_TYPE_WARNING);
                return false;
            }
            
            //read the include tags from the docxap
            $templatesIncludes = $xslDom->getElementsByTagName('include');
            foreach ($templatesIncludes as $templateInclude)
            {
                //if the template have been stored yet, it will not been saved
                if (isset($templates[$templateInclude->getAttribute('href')]))
                    continue;
                
                //create a new xsl:include tag for each template inclusion
                $domElement = $dom->createElement('xsl:include');
                $domAttribute = $dom->createAttribute('href');
                $domAttribute->value = FsUtils::get_url_path($includeURL) . $templateInclude->getAttribute('href');
                $domElement->appendChild($domAttribute);
                
                //insert the new include tag before the xsl:template tag that will be replaced
                $docxapRoot->item(0)->insertBefore($domElement, $templatesElement->item(0));
                
                //save the element in the templates array
                $templates[$templateInclude->getAttribute('href')] = $domElement;
            }
            
            //remove the current includes tag
            $docxapRoot->item(0)->removeChild($templatesInclude[$templatesIncludeURL]);
        }
        
        //save the regenerated XML content to a string
        $content = $dom->saveXML();
        return $content;
    }
    
    /**
     * Change all the templates inclusions in each docxap.xsl template that dependant of the given node with the new name
     * Used after a name project, server o section has been changed
     * @param Node $node
     * @param Node $oldNode
     * @return boolean
     */
    public static function rename_include_templates(Node $node, Node $oldNode)
    {
        if ($node->GetNodeName() == $oldNode->GetNodeName())
            return true;
        
        //if the node type is not a project, server or section, it do nothing 
        if ($node->GetNodeType() != Ximdex\Services\NodeType::PROJECT and $node->GetNodeType() != Ximdex\Services\NodeType::SERVER
               and $node->GetNodeType() != Ximdex\Services\NodeType::SECTION)
            return true;
            
        //load the project ID of the node given
        $projectId = $node->GetProject();
        
        return self::rename_templates_in_node($node, $oldNode, $projectId);
    }
    
    /**
     * Change the references to a templates_include.xsl in a docxap file with a templates node given
     * @param Node $node
     * @param Node $oldNode
     * @param int $projectId
     * @param array $urls
     * @return boolean
     */
    private static function rename_templates_in_node(Node $node, Node $oldNode, $projectId, $urls = array())
    {
        //look for template folder
        $templateFolderId = $node->GetChildren(Ximdex\Services\NodeType::TEMPLATES_ROOT_FOLDER);
        if ($templateFolderId)
        {
            $templateFolder = new Node($templateFolderId[0]);
            
            //look for docxap template
            $docxapTemplateId = $templateFolder->GetChildByName('docxap.xsl');
            if ($docxapTemplateId)
            {
                $docxapTemplate = new Node($docxapTemplateId);
                $content = $docxapTemplate->GetContent();
                
                //change the include references for templates_include.xsl to the new path
                $dom = new DOMDocument();
                $dom->formatOutput = true;
                $dom->preserveWhiteSpace = false;
                if (!@$dom->loadXML($content))
                {
                    $error = 'Can\'t load a dependant docxap template to update the new name in include references';
                    $this->messages->add($error, MSG_TYPE_WARNING);
                    return false;
                }
                //generate the new and old template URL for this node
                $oldTemplateURL = App::getValue('UrlRoot') . App::getValue('NodeRoot') . $node->GetRelativePath($projectId, $oldNode)
                        . '/templates/templates_include.xsl';
                $newTemplateURL = App::getValue('UrlRoot') . App::getValue('NodeRoot') . $node->GetRelativePath($projectId)
                        . '/templates/templates_include.xsl';
                //read the include tags from the docxap
                $root = $dom->getElementsByTagName('stylesheet');
                $xPath = new DOMXPath($dom);
                $templatesIncludes = $xPath->query('//xsl:stylesheet/xsl:include');
                foreach ($templatesIncludes as $templateInclude)
                {
                    //if the template file is not a templates_include.xsl file continue to the next one
                    if (FsUtils::get_url_file($templateInclude->getAttribute('href')) == 'templates_include.xsl')
                    {
                        if ($templateInclude->getAttribute('href') == $oldTemplateURL)
                        {
                            //replace the xsl:include tag for each template inclusion with the new URL
                            $domElement = $dom->createElement('xsl:include');
                            $domAttribute = $dom->createAttribute('href');
                            $domAttribute->value = $newTemplateURL;
                            $domElement->appendChild($domAttribute);
                            $root->item(0)->replaceChild($domElement, $templateInclude);
                            
                            //store the new include URL linked to the old one (for the next templates)
                            $urls[$oldTemplateURL] = $newTemplateURL;
                        }
                        elseif (isset($urls[$templateInclude->getAttribute('href')]))
                        {
                            //check if the template inclusion is in the parent nodes list, and replace it
                            $domElement = $dom->createElement('xsl:include');
                            $domAttribute = $dom->createAttribute('href');
                            $domAttribute->value = $urls[$templateInclude->getAttribute('href')];
                            $domElement->appendChild($domAttribute);
                            $root->item(0)->replaceChild($domElement, $templateInclude);
                        }
                    }
                }
                $content = $dom->saveXML();
                if ($content === false)
                    return false;
                if ($docxapTemplate->SetContent($content) === false)
                    return false;
            }
        }
        
        //get children of the node
        $childNodes = $node->GetChildren();
        foreach ($childNodes as $childNode)
        {
            $childNode = new Node($childNode);
            //only project, servers and section/subsections can storage template folders
            if ($childNode->GetNodeType() == Ximdex\Services\NodeType::PROJECT or $childNode->GetNodeType() == Ximdex\Services\NodeType::SERVER
                    or $childNode->GetNodeType() == Ximdex\Services\NodeType::SECTION)
            {
                //call in recursive mode with the child node
                $res = self::rename_templates_in_node($childNode, $oldNode, $projectId, $urls);
                if ($res === false)
                    return false;
            }
        }
        return true;
    }
    
    /**
     * Move a template node to another include templates and remove the previous reference
     * @param int $targetParentID
     * @return boolean
     */
    public function move_node($targetParentID)
    {
        //locate the NodeID for the parent templates node
        $templatesId = $this->parent->GetParent();
        $templates = new Node ($templatesId);
        if (!$templates->GetID())
        {
            $this->messages->add('The node has not a parent node');
            return false;
        }
        $parentId = $templates->GetParent();
        
        //remove the template
        if ($this->removeIncludeFile($this->parent->GetNodeName(), $parentId, $this->parent->GetNodeType()) === false)
            return false;
        
        //include the template
        if ($this->setIncludeContent($this->parent->GetNodeName(), $targetParentID, $this->parent->GetNodeType(), null) === false)
            return false;
        return true;
    }
}