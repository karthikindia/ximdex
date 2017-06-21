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


namespace Ximdex\MVC;

use Codeception\Lib\Interfaces\Web;
use Laravel\Lumen\Application;
use Laravel\Lumen\Routing\Controller;
use Ximdex\Logger;
use Ximdex\MVC\Render\AbstractRenderer;
use Ximdex\Notifications\EmailNotificationStrategy;
use ModulesManager;
use Ximdex\Parsers\ParsingJsGetText;
use Ximdex\Runtime\Response;
use Ximdex\Runtime\WebRequest;
use Ximdex\Utils\Messages;
use Ximdex\Utils\Serializer;
use Ximdex\Models\User;
use Ximdex\Models\Action;
use Ximdex\Models\Node;
use Ximdex\Runtime\App;
use Ximdex\Runtime\Request;
use Ximdex\Utils\Factory;
use Ximdex\Utils\QueryManager;
use Ximdex\Utils\Session;
use Ximdex\Notifications\XimdexNotificationStrategy;

/**
 *
 * @brief Base abstract class for Actions
 *
 *  Base abstract class for Actions who provides basic funcionality like rendering
 *  css/js inclusion and redirection
 *
 */
class ActionAbstract extends Controller
{

    /**
     * Keeps the js to use
     */
    public $displayEncoding;
    public $actionMethod;
    public $actionModule;
    /**
     * @var array
     */
    private $_js = array();

    /**
     * keeps the css to use
     * @var array
     */
    private $_css = array();

    /**
     * Action name
     */
    private $actionName = '';

    /**
     * Action description
     */
    private $actionDescription = '';

    /**
     * Action renderer
     */
    /**
     * @var AbstractRenderer
     */
    public $renderer;

    /**
     * Action command
     * @var String
     */
    public $actionCommand;
    /**
     * @var bool
     */
    protected $endActionLogged = false;

    /**
     * @var WebRequest
     */
    var $request;

    /**
     * @var Response
     */
    var $response;
    /**
     * @var bool
     */
    var $hasError;
    /**
     * @var
     */
    var $msgError;
    /**
     * @var Messages
     */
    var $messages;

    /**
     * ActionAbstract constructor.
     * @param null $_render
     */
    public function __construct($_render = null, WebRequest $request = null)
    {

        $this->messages = new Messages();

        $this->response = new Response();

        if (empty($request)){
            $request = WebRequest::capture();
        }

        $this->request = $request;

        $this->displayEncoding = App::getValue('displayEncoding');


        $rendererClass = $this->_get_render($_render);



        $factory = new Factory(RENDERER_ROOT_PATH, '');




        $this->renderer = $factory->instantiate($rendererClass . 'Renderer');


        $this->renderer->set("_BASE_TEMPLATE_PATH", sprintf('%s/xmd/template/%s/', XIMDEX_ROOT_PATH, $rendererClass));

    }

    /**
     * @param $actionName
     * @param $module
     * @param $actionId
     * @param $nodeId
     * @return array
     */
    private function getActionInfo($actionName, $module, $actionId, $nodeId)
    {

        unset($actionId);

        $nodeTypeId = "";
        if (!is_null($nodeId)) {
            $node = new Node();
            $nodeTypeId = $node->find('IdNodeType', 'IdNode = %s', array($nodeId), MONO);
            $nodeTypeId = $nodeTypeId[0];
        }


        $action = new Action();
        $data = $action->find(
            'Command, Name, Description, Module',
            'IdNodeType = %s and Command = %s and Module is %s',
            array($nodeTypeId, $actionName, $module)
        );

        if (!empty($data)) {
            $data = $data[0];
        }

        //debug::log($data,$actionName, $module, $actionId, $nodeId);
        return $data;
    }

    /**
     * Execute the action
     */
    /**
     * @param $request WebRequest
     */
    function execute(WebRequest $request)
    {
        $method = ($var = $this->request->input('method')) ? $var : 'index';
        $actionInfo = $this->getActionInfo(
            $this->request->input('action'),
            $this->request->input('module'),
            $this->request->input('actionid'),
            $this->request->input('nodeid')
        );

        if (!empty($actionInfo)) {
            $this->actionCommand = $actionInfo['Command'];
            $this->actionName = $actionInfo['Name'];
            $this->actionDescription = $actionInfo['Description'];
            $this->actionModule = isset($actionInfo['Module']) ? $actionInfo['Module'] : null;
        }


        if (method_exists($this, $method)) {
            $this->actionMethod = $method;
            $this->logInitAction();
            $this->$method();
        } else {
            Logger::debug("MVC::ActionAbstract Metodo {$method} not found");
        }

    }

    private function getDefaultLogMessage()
    {
        $user = Session::get("userID") ? "by " . Session::get("userID") : "";

        $moduleString = '';

        if (isset($this->actionModule)) {
            $moduleString = $this->actionModule ? "in module {$this->actionModule}." : "";

        }

        return $moduleString . get_class($this) . "->{$this->actionMethod} {$user}";

    }

    private function logInitAction()
    {

        $this->endActionLogged = false;
        Logger::info("Init " . $this->getDefaultLogMessage(), "action_logger");
        Logger::debug("Request: " . print_r($this->request, true), "action_logger");

    }

    protected function logEndAction($success = true, $message = null)
    {

        $message = $message ? ". $message" : "";
        if ($success)
            Logger::info("FINISH OK " . $this->getDefaultLogMessage() . " $message", "action_logger");
        else
            Logger::error("FINISH FAIL " . $this->getDefaultLogMessage() . " $message", "action_logger");

        $this->endActionLogged = true;
    }

    protected function logSuccessAction($message = null)
    {
        $this->logEndAction(true, $message);
    }

    protected function logUnsuccessAction($message = null)
    {
        $this->logEndAction(false, $message);
    }


    /**
     * Renders the action
     *

     */
    /**
     * @param null $arrValores
     * @param null $view
     * @param null $layout
     * @param bool $return
     * @return null
     */
    function render($arrValores = NULL, $view = NULL, $layout = NULL, $return = FALSE)
    {

        if (!$this->endActionLogged)
            $this->logSuccessAction();

        if (is_null($this->renderer)) {
            $this->_setError("Renderizador no definido", "Actions");
            return null;
        }

        //Send the encoding to the browser
        //$this->response->set('Content-type', "text/html; charset=$this->displayEncoding");

        // Render default values
        if ($view != NULL) $this->request->setParam("method", $view);

        // Visualize action headers ( Action name + description + node_path )
        $this->request->setParam("view_head", 1);

        // Saving in the request the css and js(passed by gettext before)
        $this->request->setParam("locale", Session::get('locale'));

        $getTextJs = new ParsingJsGetText();

        $this->request->setParam("js_files", $getTextJs->getTextArrayOfJs($this->_js));
        $this->request->setParam("css_files", $this->_css);

        $reflector = new \ReflectionClass(get_class($this));
        $fileName = $reflector->getFileName();
        $dirName = dirname($fileName);

        // Usefull values
        $arrValores['_XIMDEX_ROOT_PATH'] = XIMDEX_ROOT_PATH;
        $arrValores['_ACTION_COMMAND'] = $dirName;
        $arrValores['_ACTION_NAME'] = $this->actionName;
        $arrValores['_ACTION_DESCRIPTION'] = $this->actionDescription;

        $query = App::get('\Ximdex\Utils\QueryManager');
        $arrValores['_MESSAGES_PATH'] = $query->getPage() . $query->buildWith();


        // Passing specified values
        $this->request->setParameters($arrValores);
        $this->renderer->setParameters($this->request->all());

        // If layout was not specified
        if (empty($layout) || $layout == "messages.tpl") {

            if ($this->request->getParam("ajax") == "json") {

                //If there are some errors and op=json, errors are returned in json format
                if (isset($arrValores["messages"]) /*&& isset($arrValores["messages"][0])*/) {
                    $this->sendJSON($arrValores["messages"]);
                } else {
                    $this->sendJSON($arrValores);
                }

            } else if (isset($arrValores["messages"]) /*&& isset($arrValores["messages"][0])*/) {

                //If there are some arrores and op is not found, the errors are shown in a message.
                $layout = 'messages.tpl';
                if ($this->request->getParam("nodeid") > 0) {

                    $this->reloadNode($this->request->getParam("nodeid"));
                    $this->request->setParam("js_files", $getTextJs->getTextArrayOfJs($this->_js));
                }

            } else {

                // If there is no errors, $view is visualized
                $layout = 'default.tpl';
            }
        }
        $this->renderer->setTemplate(XIMDEX_ROOT_PATH . '/xmd/template/Smarty/layouts/' . $layout);
//		$this->request->setParam("outHTML", $this->renderer->render($view));
        $output = $this->renderer->render($view);

        // Apply widgets renderer after smarty renderer
        $output = $this->_renderWidgets($output);

        if ($return === true) {
            return $output;
        }
        $this->request->setParam('outHTML', $output);
        $this->request->setParameters($this->renderer->getParameters());
        //$this->response->sendHeaders();
        if ($this->request->getParam("out") == "WEB") {
            echo $this->request->getParam("outHTML");
        }

        return null;
    }

    /**
     * Renders the widgets of an action
     *
     * @param $output
     */
    function _renderWidgets($output)
    {

        // DEBUG: Apply widgets renderer after smarty renderer
        $factory = new  Factory(RENDERER_ROOT_PATH, '');
        $wr = $factory->instantiate('WidgetsRenderer');
        $params = $this->renderer->getParameters();

        // Important!, clean up assets
        $params['css_files'] = array();
        $params['js_files'] = array();

        $wr->setParameters($params);
        $output = $wr->render($output);
        // DEBUG: Apply widgets renderer after smarty renderer

        return $output;
    }

    /**
     * Redirects the action to another
     */
    /**
     * @param null $method
     * @param null $actionName
     * @param null $parameters
     */
    function redirectTo($method = NULL, $actionName = NULL, $parameters = NULL)
    {
        if (empty($method)) {
            $method = 'index';
        }

        $this->request["redirect_other_action"] = 1;
        if (!empty($actionName)) {
            $action = new Action();
            $idNode = $this->request->getParam("nodeid");

            $node = new Node($idNode);
            $idAction = $action->setByCommand($actionName, $node->get('IdNodeType'));

            // IMPORTANT: If idAction is empty, that node has no permissions on the action.
            // Display the error and exit or an evil redirection loop will crash your server!
            if (intval($idAction) < 1) {
                $this->messages->add(sprintf(_('The action %s cannot be executed on the selected node'), $actionName), MSG_TYPE_ERROR);
                $values = array('messages' => $this->messages->messages);
                $this->render($values);
                die();
            }


            $this->request["actionid"] = $idAction;
            $this->request["action"] = $action->GetCommand();
        }


        $this->request["method"] = $method;

        $app = Application::getInstance();
        dump($this->request->all());
        $app->run($this->request);
    }

    /**
     * Recargamos el arbol sobre el nodo especificado
     */
    /**
     * @param $idnode
     */
    function reloadNode($idnode)
    {

        // TODO search and destroy the %20 generated in the last char of the query string
        $queryManager = new QueryManager(false);
        $file = sprintf('%s%s',
            '/',
            $queryManager->buildWith(array(
                'xparams[reload_node_id]' => $idnode,
                'js_file' => 'reloadNode',
                'method' => 'includeDinamicJs',
                'void' => 'SpacesInIE7HerePlease'
            ))
        );

        $this->addJs(urldecode($file));
    }

    /**
     * <p>Genera y añade el código Javascript necesario para
     * lanzar la siguiente acción tras la actual.</p>
     * <p>Ximdex será el encargado de obtener la acción siguiente a la actual
     * y ejecutarla sobre el nodo especificado.</p>
     *
     * @param $idnode int  id del nodo sobre el que ejecutar la siguiente acción
     */
    /*function nextAction($idnode) {
        $queryManager = new QueryManager(false);
        $fileNextAction = sprintf('%s%s',
            '/',
            $queryManager->buildWith(array(
                    'xparams[id_node]' => $idnode,
                    'xparams[action_name]' => str_replace("Action_", "", get_class($this)),
                    'js_file' => 'nextAction',
                    'method' => 'includeDinamicJs',
                    'void' => 'SpacesInIE7HerePlease'
            ))
        );

        $this->addJs(urldecode($fileNextAction));
    }*/

    /**
     * @param $_js
     * @param string $_module
     * @param null $params
     * @return array|string
     */
    public function addJs($_js, $_module = 'XIMDEX', $params = null)
    {

        if ('XIMDEX' != $_module) {
            $path = ModulesManager::path($_module);
            $_js = $path . $_js;
        }

        if ($params === null) {

            return $this->_js[] = $_js;
        } else {

            // if "params" attribute is set, javascript will be parsed
            return $this->_js[] = array(
                'file' => $_js,
                'params' => $params
            );
        }
    }

    /**
     * @param $_css
     * @param string $_module
     */
    public function addCss($_css, $_module = 'XIMDEX')
    {

        if ('XIMDEX' != $_module) {
            $path = ModulesManager::path($_module);
            $_css = $path . $_css;
        }

        $this->_css[] = App::getValue('UrlRoot') . $_css;
    }

    /**
     * @param null $rendererClass
     * @return mixed|null|string
     */
    private function _get_render($rendererClass = null)
    {




        if ($rendererClass == null) {
            if (Session::get('debug_render') > 0) {
                switch (Session::get('debug_render')) {
                    case 1:
                        $rendererClass = "Smarty";
                        break;
                    case 2:
                        $rendererClass = "Json";
                        break;
                    case 3:
                        $rendererClass = "Debug";
                        break;
                    default:
                        $rendererClass = $this->request->input("renderer");
                }
            } else {
                $rendererClass = $this->request->input("renderer");
            }
        }

        //Si no hay definido ning�n render
        if (!$rendererClass) {
            $rendererClass = "Smarty";
        }

        //Guardamos el render
        $this->request['renderer'] = $rendererClass;
        return $rendererClass;
    }

    /**
     * @param $data
     */
    public function sendJSON($data)
    {
        if (!$this->endActionLogged)
            $this->logSuccessAction();
        if (isset($data['status']) && is_int($data['status'])) {
            header('HTTP/1.1 {$data["status"]}');
        }
        header(sprintf('Content-type: application/json; charset=', $this->displayEncoding));
        $data = Serializer::encode(SZR_JSON, $data);
        echo $data;
        die();
    }

    /**
     * @param $data
     * @param null $etag
     */
    public function sendJSON_cached($data, $etag = null)
    {
        if ($etag) {
            $data = Serializer::encode(SZR_JSON, $data);
            $hash = md5($data);
            if ($hash == $etag) {
                header('HTTP/1.1 304 Not Modified');
                header('Content-Length: 0');
                die();
            } else {
                header(sprintf('Content-type: application/json; charset=', $this->displayEncoding));
                $data = rtrim($data, "}");
                $data = $data . ', "etag": "' . $hash . '"}';
                echo $data;
                die();
            }

        } else {
            header(sprintf('Content-type: application/json; charset=', $this->displayEncoding));
            $data = Serializer::encode(SZR_JSON, $data);
            $hash = md5($data);
            $data = rtrim($data, "}");
            $data = $data . ', "etag": "' . $hash . '"}';
            echo $data;
            die();
        }
    }

    /**
     * Remplace files con [LANG] to i18n file
     * Example:
     *  /var/www/ximdex/xmd/images/[LANG]/pingu.gif -> /var/www/ximdex/xmd/images/es/pingu.gif
     *  or
     *  /var/www/ximdex/xmd/images/ximNEWS/pingu_[LANG].gif -> /var/www/ximdex/xmd/images/ximNEWS/pingu_es.gif
     *  or ...
     *  This can be also done in html with the smarty var locale
     */
    /**
     * @param $file
     * @param null $_lang
     * @param null $_default
     * @return mixed|null
     */
    function i18n_file($file, $_lang = null, $_default = null)
    {
        $_file = null;

        //Checking if the file is existing for the passed language
        if ($_lang != null) {
            $_file = str_replace("[LANG]", $_lang, $file);
            if (file_exists($_file))
                return $_file;
        }

        //if the associated file for this language is not existing, checking with system language
        $_lang = Session::get('locale');
        if ($_lang != null) {
            $_file = str_replace("[LANG]", $_lang, $file);
            if (file_exists($_file))
                return $_file;
        }

        $_lang = DEFAULT_LOCALE;
        if ($_lang != null) {
            $_file = str_replace("[LANG]", $_lang, $file);
            if (file_exists($_file))
                return $_file;
        }

        return $_default;
    }

    /**
     *
     */
    protected function renderMessages()
    {
        $this->render(array('messages' => $this->messages->messages));
        die();
    }

    /**
     * Decides if a tour is be able to be launched automatically given an user
     */
    /**
     * @param $userId
     * @param null $action
     * @return bool
     */
    public function tourEnabled($userId, $action = null)
    {
        unset($userId);

        if (!ModulesManager::isEnabled('ximTOUR')) {
            return false;
        }
        // $actionsStats = new ActionsStats() ;
        $numReps = App::getValue('ximTourRep');
        if (!$action) {
            // $action = $this->actionCommand;
        }
        $user = new User (Session::get("userID"));
        $result = $user->GetNumAccess();
        return ($result === null || $result < $numReps) ? true : false;
    }

    /**
     * @param $subject
     * @param $content
     * @param $to
     * @return array
     */
    protected function sendNotifications($subject, $content, $to)
    {
        $from = Session::get("userID");
        $emailNotification = new EmailNotificationStrategy();
        $result = $emailNotification->sendNotification($subject, $content, $from, $to);
        $messagesNotification = new XimdexNotificationStrategy();
        $messagesNotification->sendNotification($subject, $content, $from, $to);

        foreach ($result as $idUser => $resultByUser) {
            $user = new User($idUser);
            $userEmail = $user->get('Email');
            if ($resultByUser) {
                $this->messages->add(sprintf(_("Message successfully sent to %s"), $userEmail), MSG_TYPE_NOTICE);
            } else {
                $this->messages->add(sprintf(_("Error sending message to the mail address %s"), $userEmail), MSG_TYPE_WARNING);
            }
        }

        return $result;
    }

    /**
     * @param $request
     */
    function setRequest($request)
    {
        $this->request = $request;
    }
    /**
     * TODO: Cambiar toda la gesti�n de errores en base a variable booleana + array simple por el objeto messages
     * Getter
     */
    /**
     *
     */
    function hasError()
    {
        if (isset ($this->hasError)) return $this->hasError;
    }
    /**
     *
     */
    function getMsgError()
    {
        if (isset ($this->msgError)) {
            return $this->msgError;
        }
    }
    /**
     * @param $msg
     * @param $module
     */
    function _setError($msg, $module)
    {
        unset( $module ) ;
        $this->hasError = true;
        $this->msgError = $msg;
        // Registra un apunte en el log
        Logger::error($msg);
    }

}