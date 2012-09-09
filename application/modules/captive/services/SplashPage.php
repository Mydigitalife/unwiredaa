<?php

class Captive_Service_SplashPage
{
    /**
     * Find the active splash page for a device group
     *
     * @param Captive_Model_Group $group
     * @return Captive_Model_SplashPage
     */
    public function findActiveSplashPage(Captive_Model_Group $group)
    {
        $mapperSplash = new Captive_Model_Mapper_SplashPage();

        $splashPage = null;

        $parent = $group;
        /**
         * Walk the tree up to the root node.
         * It is assumed that at least the root node will have active splash page set.
         */
        do {
            $splashPage = $mapperSplash->findOneBy(array('active' => 1,
                                                         'selected' => 1,
                                                         'group_id' => $group->getGroupId()));

            if ($splashPage) {
                return $splashPage;
            }

            $parent = $parent->getParent();
        } while (null !== $parent);

        return null;
    }

    public function getLayoutContentSortedArray(Captive_Model_Layout $layout, $splashId = 0, $templateId = 0)
    {
        $mapperContent = new Captive_Model_Mapper_Content();

        $criteria = array('layout_id' => $layout->getLayoutId());

        $templateOverrideIds = array();
        $contents = array();

        $currentUser = Zend_Auth::getInstance()->getIdentity();
        $acl = Zend_Registry::get('acl');

        if ($splashId) {
            $criteria['splash_id'] = $splashId;

            $contents = $mapperContent->findBy($criteria,
                                               null,
                                               array('column ASC',
                                                     'order ASC'));

            foreach ($contents as $content) {
                if ($content->getTemplateContent()) {
                    $templateOverrideIds[] = $content->getTemplateContent();
                }
            }
        }

        $templateContents = array();

        if ($templateId) {
            unset($criteria['splash_id']);
            $criteria['template_id'] = $templateId;

            $templateContents = $mapperContent->findBy($criteria,
                                                       null,
                                                       array('column ASC',
                                                             'order ASC'));

            foreach ($templateContents as $index => $content) {
                if (in_array($content->getContentId(), $templateOverrideIds)) {
                    unset($templateContents[$index]);
                }
            }
        }

        $contents = array_merge($contents, $templateContents);

        $placements = array();

        /**
         * Try to read layout placements from template configuration
         * row1 => array(cols)
         * row2 => array(othercols)
         */
        if (file_exists(PUBLIC_PATH . '/data/templates/' . $templateId .'/template.xml')) {
            try {
                $config = new Zend_Config_Xml(PUBLIC_PATH . '/data/templates/' . $templateId .'/template.xml');

                if (isset($config->templates) && isset($config->templates->{$layout->getLayout()})) {
                    $placements = $config->templates->{$layout->getLayout()}->toArray();

                    if (isset($placements['rows'])) {
                        $placements = $placements['rows'];
                    }

                    foreach ($placements as $row => $cols) {

                        foreach ($cols as $key => $containers) {
                            $containers = explode(',', $containers);

                            $cols[$key] = array();
                            foreach ($containers as $container) {
                                $container = preg_replace('/[^\-\d]*/i', '', $container);
                                $cols[$key][] = empty($container) ? 0 : (int) $container;
                            }

                        }

                        $placements[$row] = $cols;
                    }
                }
            } catch (Exception $e) {
                $placements = array();
            }
        }

        /**
         * There's no layout placement config.
         * We put all columns in a separate row.
         */
        if (empty($placements)) {
            $placements['special'] = array('all' => array());
            $placements['main'] = array();

            foreach ($contents as $content) {
                if ($content->getColumn() < 0) {
                    $targetRow = 'special';
                    $targetCol = 'all';
                } else {
                    $targetRow = $content->getColumn() == 0 ? 'main' : 'row' . $content->getColumn();
                    $targetCol = $content->getColumn();
                }

                if (!isset($placements[$targetRow])) {
                    $placements[$targetRow] = array();
                }

                if (!isset($placements[$targetRow][$targetCol])) {
                    $placements[$targetRow][$targetCol] = array();
                }

                if (!in_array($content->getColumn(), $placements[$targetRow][$targetCol])) {
                    $placements[$targetRow][$targetCol][] = $content->getColumn();
                }
            }
        }

        $sortedContent = array();

        /**
         * Make sorted content keys positions same as placements
         */

        foreach ($placements as $row => $cols) {
            $sortedContent[$row] = array();

            foreach ($cols as $key => $containers) {
                $sortedContent[$row][$key] = array();

                foreach ($containers as $container) {
                    $sortedContent[$row][$key][$container] = array();
                }
            }
        }

        /**
         * @todo Optimize this! Too many nested loops.
         *       Need a better algo.
         */
        foreach ($contents as $content) {
            $colKey = false;
            $rowKey = false;
            foreach ($placements as $row => $cols) {
                foreach ($cols as $key => $containers) {
                    $colKey = array_search($content->getColumn(), $containers);
                    if ($colKey === false) {
                        continue;
                    }

                    $rowKey = $key;

                    break 2;
                }
            }

            if ($colKey === false || $rowKey == false) {
                // @todo What do we do if the col isn't listed anywhere?
            }

            if (!isset($sortedContent[$row])) {
                $sortedContent[$row] = array();
            }

            if (!isset($sortedContent[$row][$rowKey])) {
                $sortedContent[$row][$rowKey] = array();
            }

            if (!isset($sortedContent[$row][$rowKey][$content->getColumn()])) {
                $sortedContent[$row][$rowKey][$content->getColumn()] = array();
            }
            $arrayContent = $content->toArray();
            $arrayContentData = array();
            foreach ($arrayContent['data'] as $data) {
                $data = $data->toArray();
                $arrayContentData[] = $data;
            }
            $arrayContent['data'] = $arrayContentData;

            $arrayContent['edit_allowed'] = 0;
            $arrayContent['delete_allowed'] = 0;

            if ($acl->isAllowed($currentUser, $content, 'edit')) {
                $arrayContent['edit_allowed'] = 1;
            }

            if ($acl->isAllowed($currentUser, $content, 'delete')
	            && ($splashId && $content->getSplashId())
	               || (!$splashId && $content->getTemplateId())) {

                    $arrayContent['delete_allowed'] = 1;
            }

            $sortedContent[$row][$rowKey][$content->getColumn()][] = $arrayContent;
        }

        return $sortedContent;


    }

    public function getSplashPageContents(Captive_Model_SplashPage $splashPage/*,
                                          Captive_Model_Language $language*/)
    {
        $mapperContent = new Captive_Model_Mapper_Content();

        $criteria = array('splash_id'   => $splashPage->getSplashId(),
                          /*'language_id' => $language->getLanguageId(),
                          'mobile'      => 0,*/
                          'type'        => 'content');

      /*  if ($splashPage->isMobile()) {
            $criteria['mobile'] = 1;
        }*/

        $contents = $mapperContent->findBy($criteria/*,
                                           null,
                                           '`order` ASC'*/);

        $templateOverrideIds = array();

        foreach ($contents as $content) {
            if ($content->getTemplateContent()) {
                $templateOverrideIds[] = $content->getTemplateContent();
            }
        }

        unset($criteria['splash_id']);
        $criteria['template_id'] = $splashPage->getTemplateId();

        $templateContents = $mapperContent->findBy($criteria/*,
                                                   null,
                                                   '`order` ASC'*/);

        foreach ($templateContents as $index => $content) {
            if (in_array($content->getContentId(), $templateOverrideIds)) {
                unset($templateContents[$index]);
            }
        }

        $contents = array_merge($contents, $templateContents);

        return $contents;

    }

    public function getTemplateContent(Captive_Model_Template $template)
    {
        $mapperContent = new Captive_Model_Mapper_Content();

        $contents = $mapperContent->findBy(array('template_id' => $template->getTemplateId()));

        return $contents;
    }

    public function updateOrder($desktop, $mobile, $template = false, $splashId = 0, $templateId = 0)
    {
        $mapperContent = new Captive_Model_Mapper_Content();

        $contents = array('desktop' => $desktop, 'mobile' => $mobile);

        $forUpdate = array();

        foreach ($contents as $layoutName => $layout) {
            foreach ($layout as $column => $order) {
                $column = (int) $column;

                $position = 0;

                foreach ($order as $contentId) {
                    $position++;
                    $contentId = (int) $contentId;

                    if (!isset($forUpdate[$contentId])) {
                        $content = $mapperContent->find($contentId);
                    } else {
                        $content = $forUpdate[$contentId];
                    }

                    if (!$content) {
                        continue;
                    }

                    if ($template && $content->getSplashId()) {
                        continue;
                    }

                    $pageContent = null;
                    if (!$template && $content->getTemplateId()) {
                        /**
                         * Content is not editable
                         */
                        if (!$content->isEditable()) {
                            continue;
                        }

                        $pageContent = $mapperContent->findOneBy(array('template_content' => $content->getContentId()));

                        if (!$pageContent) {
                            /**
                             * Clone the content so we can move it
                             */
                            $pageContent = clone $content;
                            $pageContent->setContentId(null)
                                        ->setTemplateId(null)
                                        ->setTemplateContent($content->getContentId())
                                        ->setSplashId($splashId);

                            try {
                                $mapperContent->save($pageContent);
                            } catch (Exception $e) {
                                continue;
                            }
                        }
                    }

                    /**
                     * Check and swap template widget with splashpage widget
                     */
                    if ($pageContent) {
                        $forUpdate[$content->getContentId()] = $pageContent;
                        $content = $pageContent;
                    } else {
                        $forUpdate[$content->getContentId()] = $content;
                    }

                    if ($layoutName == 'desktop') {
                        $content->setColumn($column);
                    }

                    $data = $content->getData();

                    foreach($data as $contentData) {
                        if ($layoutName == 'desktop') {
                            if($contentData->isMobile()) {
                                continue;
                            }
                        } else {
                            if(!$contentData->isMobile()) {
                                continue;
                            }
                        }

                        $contentData->setOrder($position);
                    }
                }
            }
        }

        foreach ($forUpdate as $content) {
            $mapperContent->save($content);
        }

        return true;
    }

    public function saveTemplateContents(Captive_Model_Template $template, array $contents)
    {
        /**
         * @todo this is quick and dirty workaround for 22nd Nov
         */

        $mapperContent = new Captive_Model_Mapper_Content();

        $success = 0;

        foreach ($contents as $type => $languageData) {
            if ($type != 'terms' && $type != 'imprint') {
                continue;
            }

            foreach ($languageData as $languageId => $content) {
                try {
                    $model = $mapperContent->getEmptyModel();
                    $model->fromArray($content);
                    $model->setLanguageId($languageId)
                          ->setType($type)
                          ->setTemplateId($template->getTemplateId());

                    $mapperContent->save($model);
                    $success++;
                } catch (Exception $e) {
                    throw $e;
                }
            }
        }

        /**
         * @todo add html content blocks
         */
        return $success;
    }

    public function getGroupTemplates($group)
    {
        $groupId = $group;
        if ($groupId instanceof  Groups_Model_Group) {
            $groupId = $group->getGroupId();
        }

        $serviceGroups = new Groups_Service_Group();

        $group = $serviceGroups->findGroup($groupId, true, false);

        if (!$group) {
            return array();
        }

        $ids = array($group->getGroupId());

        $parent = $group;
        while ($parent = $parent->getParent()) {
            $ids[] = $parent->getGroupId();
        }

        $mapperTemplate = new Captive_Model_Mapper_Template();

        $templates = $mapperTemplate->findBy(array('group_id' => $ids));

        if (!$templates) {
            $templates = array();
        }

        return $templates;
    }

    public function saveSplashPageContents(Captive_Model_SplashPage $splashPage, array $contents)
    {
        /**
         * @todo this is quick and dirty workaround for 22nd Nov
         */

        $mapperContent = new Captive_Model_Mapper_Content();

        $success = 0;

        foreach ($contents as $content) {
            try {
                $model = null;
                if (isset($content['content_id'])) {
                    $model = $mapperContent->find($content['content_id']);

                }

                if (!$model) {
                    $model = $mapperContent->getEmptyModel();
                }

                $model->fromArray($content);

                if ($model->getTemplateId()) {
                    $model->setTemplateContent($model->getContentId());
                    $model->setTemplateId(null);
                    $model->setContentId(null);

                }


                $model->setType('content')
                      ->setSplashId($splashPage->getSplashId());

                $mapperContent->save($model);
                $success++;
            } catch (Exception $e) {
                throw $e;
            }
        }

        /**
         * @todo add html content blocks
         */
        return $success;
    }

    public function copyTemplate(Captive_Model_Template $template)
    {
        $mapperTemplate = new Captive_Model_Mapper_Template();

        $mapperTemplate->setEventsDisabled(true);

        $dbAdapter = $mapperTemplate->getDbTable()->getAdapter();

        $dbAdapter->beginTransaction();

        try {
            $newTemplate = clone $template;

            $newTemplate->setTemplateId(NULL)
                        ->setName($template->getName() . ' (Copy ' . date('Y-m-d H:i:s') . ')');

            $mapperTemplate->save($newTemplate);

            $mapperContent = new Captive_Model_Mapper_Content();
            $mapperContent->setEventsDisabled(true);

            $templateContents = $this->getTemplateContent($template);

            foreach ($templateContents as $content) {
                $content->setContentId(null);
                $content->setTemplateId($newTemplate->getTemplateId());

                $mapperContent->save($content);
            }

            $serviceFiles = new Captive_Service_Files();

            if (!$serviceFiles->copyContentDirectory($template, $newTemplate)) {
                throw new Unwired_Exception('Cannot copy template content.', 500);
            }

            $dbAdapter->commit();

            $mapperTemplate->setEventsDisabled(false)
                           ->sendEvent('copy', $newTemplate, $newTemplate->getTemplateId(), $newTemplate->toArray());

        } catch (Exception $e) {
            $dbAdapter->rollBack();
            throw $e;
        }

        return $newTemplate;
    }

    public function unpackTemplate(Captive_Model_Template $template, $file)
    {
		$archive = new ZipArchive();

		if (!$archive->open($file)) {
			throw new Unwired_Exception('Cannot open template package');
		}

		$templateDir = PUBLIC_PATH . '/data/templates/' . $template->getTemplateId();
		if (!file_exists($templateDir)) {
			@mkdir($templateDir);
		}
		if (!$archive->extractTo($templateDir)) {
			throw new Unwired_Exception('Cannot extract files from template package');
		}

		/**
		 * Close the archive
		 */
		$archive->close();
		$archive = null;

		/**
		 * Delete the template archive
		 */
		@unlink($file);

		/**
		 * Copy the template files to splashpage server
		 */
		if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
			$fileService = new Captive_Service_Files();

			$fileService->copyToSplashpages(array('destination' => str_ireplace('/' . $template->getTemplateId(), '', $templateDir),
                                           		  'name' => $template->getTemplateId()));
		}

		if (!file_exists($templateDir . '/template.xml')) {
		    return true;
		}

		try {
		    $config = new Zend_Config_Xml($templateDir . '/template.xml');

		    /**
		     * No data to process.
		     */
		    if (!isset($config->layouts)) {
		        $config = null;
		        return true;
		    }

		    /**
		     * There is layout data. We need to import it into the database
		     */
		    foreach ($config->layouts as $layoutData) {
		        if (!$this->importLayout($layoutData, $template)) {
		            return false;
		        }
		    }

		} catch (Exception $e) {
		    Unwired_Exception::getLog()->debug($e->getMessage());
		    return false;
		}

		return true;
    }

    public function importLayout($layoutData, $parent)
    {
        if ($layoutData instanceof Zend_Config) {
            $layoutData = $layoutData->toArray();
        }

        try {
            $mapperLayout = new Captive_Model_Mapper_Layout();
            $mapperLanguage = new Captive_Model_Mapper_Language();
            $mapperContent = new Captive_Model_Mapper_Content();

            $layoutContent = array();
            if (isset($layoutData['content']) && !empty($layoutData['content'])) {
                $layoutContent = $layoutData['content'];
            }

            unset($layoutData['content']);

            $layout = new Captive_Model_Layout();

            $layout->fromArray($layoutData);

            if ($parent instanceof Captive_Model_Template) {
                $layout->setTemplateId($parent->getTemplateId());
            } else {
                $layout->setSplashId($parent->getSplashId());
            }
            $mapperLayout->save($layout);


            foreach ($layoutContent as $contentData) {
                $content = new Captive_Model_Content();
                $content->fromArray($contentData);
                $content->setSplashId($layout->getSplashId())
                        ->setTemplateId($layout->getTemplateId())
                        ->setLayoutId($layout->getLayoutId());

                $content->setData(array());

                foreach ($contentData['data'] as $languageCode => $languageContent) {
                    $language = $mapperLanguage->findOneBy(array('code' => $languageCode));

                    if (!$language) {
                        continue;
                    }

                    $dataEntity = new Captive_Model_ContentData();
                    $dataEntity->setLanguageId($language->getLanguageId());
                    $dataEntity->fromArray($languageContent);
                    $content->addData($dataEntity);
                }

                $mapperContent->save($content);
            }
        } catch (Exception $e) {
            Unwired_Exception::getLog()->debug($e->getMessage());
            return false;
        }

        return true;
    }
}