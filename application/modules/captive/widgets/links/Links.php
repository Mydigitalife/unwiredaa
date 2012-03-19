<?php

class Widget_Links extends Unwired_Widget_Abstract
{
    protected $_config = array('decorate' => true, 'showtitle' => false, 'class' => '');

    public function render($content)
    {
        $data = unserialize($content->getContent());

        if (!$data || !is_array($data) || !count($data)) {
            return '';
        }

        if (isset($data['links'])) {
            $this->_config = array_merge($this->_config, $data);

            $data = $this->_config['links'];
        }

        $userAgent = Default_Model_UserAgent::getInstance();

        $deviceLinks = array();

        $view = Zend_Layout::getMvcInstance()->getView();

        $templatePath = $view->baseUrl('data/templates/' . $view->splashPage->getTemplateId());
        $splashpagePath = $view->baseUrl('data/splashpages/' . $view->splashPage->getSplashId());

        foreach ($data as $linkProperties) {
            $link = null;

            if (isset($linkProperties[$userAgent->getDevice()])) {
                $link = $linkProperties[$userAgent->getDevice()];
            } else if (isset($linkProperties['other'])) {
                $link = $linkProperties['other'];
            }

            if (!$link || empty($link['href']) || empty($link['content'])) {
                continue;
            }

            $link['content'] = str_replace(':templatePath:', $templatePath, $link['content']);
            $link['content'] = str_replace(':splashpagePath:', $splashpagePath, $link['content']);

            $deviceLinks[] = $link;
        }

        if ($this->_config['decorate']) {
            $viewPath = $this->getBasePath();

            if ($this->getView()->splashPage->isMobile()) {
                $viewScript = 'links-mobile.phtml';
            } else {
                $viewScript = 'links.phtml';
            }

            if (file_exists("{$viewPath}/views/scripts/{$viewScript}")) {

                $this->getView()->content = $content;
                $this->getView()->deviceLinks = $deviceLinks;
                $this->getView()->linksConfig = $this->_config;

                return $this->getView()->render($viewScript);
            }
        }

        $result = '';

        foreach ($deviceLinks as $link) {
            $escape = true;
        	if (preg_match('/\<.*\>/i', $link['content'])) {
        	    $escape = false;
        	}
            $result .= $this->getView()->formHref(array('name' => 'link' . rand(),
                                               			'escape' => $escape,
                                               			'attribs' => $link));
        }

        return $result;
    }

    public function renderAdmin($content, $params = array())
    {
        $this->getView()->assign($params);

        foreach ($content->getData() as $data) {
            $dataContent = $data->getContent();

            if ($dataContent !== null && is_string($dataContent)) {
                $dataContent = @unserialize($dataContent);
            }

            if (!is_array($dataContent)) {
                $dataContent = array();
            }

            if (!isset($dataContent['links'])) {
                $dataContent = array('links' => $dataContent);
            }

            $dataContent = array_merge($this->_config, $dataContent);

            if (!is_array($dataContent['links']) || empty($dataContent['links'])) {
                $dataContent['links'] = array(array('other' => array('href' => '', 'content' => '')));
            }

            $data->setContent($dataContent);
        }

        $this->getView()->content = $content;

        return $this->getView()->render('admin.phtml');
    }
}