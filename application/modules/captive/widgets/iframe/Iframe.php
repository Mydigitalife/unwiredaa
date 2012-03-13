<?php

class Widget_Iframe extends Unwired_Widget_Abstract
{
    protected $_defaults = array('src' => '',
    							 'height' => 300,
                                 'width' => '100%');

    public function render($content)
    {
        $data = unserialize($content->getContent());

        if (!$data || !is_array($data) || !isset($data['src'])) {
            return '';
        }

        $data = array_merge($this->_defaults, $data);

        $viewPath = $this->getBasePath();

        if ($this->getView()->splashPage->isMobile()) {
            $viewScript = 'iframe-mobile.phtml';
        } else {
            $viewScript = 'iframe.phtml';
        }

        $result = '<iframe';

        foreach ($data as $attrib => $value) {
            $result .= " {$attrib}=\"" . $this->getView()->escape($value) . '"';
        }

        $result .= '></iframe>';

        if (!file_exists("{$viewPath}/views/scripts/{$viewScript}")) {
            return $result;
        }

        return $this->getView()->partial($viewScript, array('iframe' => $result, 'data' => $data));
    }

    public function renderAdmin($content, $params = array())
    {
        foreach ($content->getData() as $data) {
            $dataContent = $data->getContent();

            if ($dataContent !== null && is_string($dataContent)) {
                $dataContent = @unserialize($dataContent);
            }

            if (!is_array($dataContent)) {
                $dataContent = array();
            }

            $dataContent = array_merge($this->_defaults, $dataContent);
            $data->setContent($dataContent);
        }

        $this->getView()->content = $content;
        $this->getView()->assign($params);

        return $this->getView()->render('admin.phtml');
    }
}