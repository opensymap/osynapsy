<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Mvc;

use Osynapsy\Event\Dispatcher as EventDispatcher;
use Osynapsy\Http\Request;
use Osynapsy\Http\Response;
use Osynapsy\Http\ResponseJson as JsonResponse;
use Osynapsy\Http\ResponseHtmlOcl as HtmlResponse;
use Osynapsy\Observer\InterfaceSubject;

abstract class Controller implements InterfaceController, InterfaceSubject
{
    use \Osynapsy\Observer\Subject;
    
    private $parameters;
    private $dispatcher;
    private $request;
    private $response;
    private $application;
    private $actions = [];
    public $model;
    
    public function __construct(Request $request = null, $application = null)
    {        
        $this->application = $application;
        $this->parameters = $request->get('page.route')->parameters;        
        $this->request = $request;
        $this->dispatcher = new EventDispatcher($this);
        $this->loadObserver();
        $this->setState('init');
        $this->init();
        $this->setState('initEnd');
    }
    
    public function actionAdd($id, $class)
    {
        $this->actions[$id] = $class;
    }
    
    public function deleteAction()
    {
        if ($this->model) {
            $this->model->delete();
        }
    }
    
    private function execAction($action, $actionParameters)
    {
        $this->setResponse(new JsonResponse());
        $this->setState($action.'ActionStart');
        if (array_key_exists($action, $this->actions)) {
            $actionClass = new \ReflectionClass($this->actions[$action]);
            $actionInstance = $actionClass->newInstance($actionClass, $actionParameters);
            $resp = $actionInstance->run();
            if ($resp) {
                $this->getResponse()->alertJs($resp);
            }
            return $this->getResponse();
        }
        if (!method_exists($this, $action.'Action')) {
            return $this->getResponse()->alertJs('No action '.$action.' exist in '.get_class($this));
        }
        
        $response = !empty($actionParameters) 
                  ? call_user_func_array( [$this, $action.'Action'], $actionParameters) 
                  : $this->{$action.'Action'}();
        $this->setState($action.'ActionEnd');
        if (!empty($response) && is_string($response)) {
            $this->getResponse()->alertJs($response);
        }
        return $this->getResponse();
    }

    final public function getApp()
    {
        return $this->application;
    }
    
    public function getDb($key = 0)
    {
        return $this->getApp()->getDb($key);
    }
    
    final public function getDbFactory()
    {
        return $this->getApp()->getDbFactory();
    }
    
    public function getDispacther()
    {
        return $this->dispatcher;
    }
    
    final public function getModel()
    {
        return $this->model;
    }
    
    public function getParameter($key)
    {
        if (!is_array($this->parameters)) {
            return null;
        }
        if (!array_key_exists($key, $this->parameters)) {
            return null;
        }
        if ($this->parameters[$key] === '') {
            return null;
        }
        return $this->parameters[$key];
    }
    
    public function getResponse()
    {
        return $this->response;
    }
    
    public function getRequest()
    {
        return $this->request;
    }    
    
    public function getState()
    {
        return $this->state;
    }
    
    abstract public function indexAction();
    
    abstract public function init();
    
    public function loadView($path, $params = [], $return = false)
    {
        $view = $this->getResponse()->getBuffer($path, $this);
        if ($return) {
            return $view;
        }
        $this->response->addContent($view);
    }
    
    public function run($action, $parameters = [])
    {
        if (!empty($action)) {
            return $this->execAction($action, $parameters);
        }        
        $this->setResponse(new HtmlResponse())->loadTemplate(
            $this->getRequest()->get('page.route')->template,
            $this
        );
        if ($this->model) {
            $this->model->find();
        }
        $response = $this->indexAction();
        if ($response) {
            $this->getResponse()->addContent($response);
        }
        return $this->getResponse();
    }
    
    public function saveAction()
    {
        if ($this->model) {
            $this->model->save();
        }
    }
    
    public function setResponse(Response $response)
    {
        return $this->response = $response;
    }
}
