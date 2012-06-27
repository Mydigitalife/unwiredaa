<?php
/**
 * Unwired AA GUI
 *
 * Author & Copyright (c) 2011 Unwired Networks GmbH
 * alexander.szlezak@unwired.at
 *
 * Licensed under the terms of the Affero Gnu Public License version 3
 * (AGPLv3 - http://www.gnu.org/licenses/agpl.html) or our proprietory
 * license available at http://www.unwired.at/license.html
 */

/**
 * Report group form
 * @author G. Sokolov <joro@web-teh.net>
 */
class Reports_Form_Group extends Unwired_Form
{
	public function init()
	{
		parent::init ();

		$this->addElement('text', 'title', array('label' => 'report_group_title',
												 'required' => true,
												 'class' => 'span-5',
												 'validators' => array('len' => array('validator' => 'StringLength',
																					  'options' => array('min' => 3)))));

		$this->addElement('text', 'description', array('label' => 'report_group_description',
													   'required' => false,
													   'class' => 'span-5'));

		$this->addElement('date', 'date_from', array('label' => 'report_group_date_from',
													 'required' => true,
													 'class' => 'span-5 datetimepicker',
													 'format' => 'dd.MM.yyyy HH:mm'/*Zend_Date::DATETIME_SHORT*/));

		$this->addElement('date', 'date_to', array('label' => 'report_group_date_to',
												   'required' => true,
												   'class' => 'span-5 datetimepicker',
												   'format' => 'dd.MM.yyyy HH:mm'/*Zend_Date::DATETIME_SHORT*/));

		$this->addElement('select', 'report_type', array('label' => 'report_group_report_type',
				'required' => true,
				'class' => 'span-5'
				));

		if ($this->getEntity()->getCodeTemplate()->getTimeframeLiveMax() !== 0) {
		    $this->getElement('report_type')->addMultiOption('manual', 'report_group_report_type_manual');
		}

		$this->getElement('report_type')->addMultiOption('interval', 'report_group_report_type_interval');

		$this->addElement('select', 'report_interval', array('label' => 'report_group_report_interval',
				'required' => true,
				'class' => 'span-5',
				'multiOptions' => array(
						'day' => 'day',
						'week' => 'week',
						'month' => 'month',
		                'quarter' => 'quarter',
						'year' => 'year',
				)
		));
		$this->addElement('textarea', 'email', array('label' => 'report_group_email',
													 'required' => false,
													 'class' => 'span-9',
													 'rows' => 5, 'style' => 'height: auto !important;',
													 'validators' => array('len' => array('validator' => 'StringLength',
																						  'options' => array('min' => 6)))));

		$decorators = $this->getElement('email')->getDecorators();

		$firstDecorators = array_slice($decorators, 0, 2, true);
		$firstDecorators[] = array('validator' => 'Description',
		                           'options' => array('class' => 'span-9'));

		$decorators = array_merge($firstDecorators, $decorators);

		$this->getElement('email')->setDecorators($decorators)
		                          ->setDescription('report_group_email_description');

        /**
         * Show group tree if report template supports group selection
         */
		if ($this->getEntity()->getCodeTemplate()->isGroupSelectionSupported()) {
    		$this->addElement('multiCheckbox', 'groups_assigned', array('label' => 'report_edit_form_group',
    											  	 			  'required' => true,
    															  'separator' => '',
    															  'registerInArrayValidator' => false));



    		$this->getElement('groups_assigned')->addErrorMessage('report_edit_form_error_group');

    		$this->addElement('hidden', 'available_roles', array('label' => 'report_edit_form_group_role',
    															 'required' => false,
    															 'class' => 'span-5',
    															 'registerInArrayValidator' => false));
		}/* else {
		    $this->addElement('hidden', 'groups_assigned', array( 'required' => true,
    															  'isArray' => false,
		                                                          'value' => key(Zend_Auth::getInstance()->getIdentity()->getGroupsAssigned())));
		}*/

		$this->addElement('select', 'timeframe', array('label' => 'report_group_edit_timeframe',
		                                               'required' => true,
		                                               'value' => $this->getEntity()->getCodeTemplate()->getTimeframeDefault(),
		                                               'multiOptions' => array('manual' => 'report_group_edit_timeframe_manual',
		                                                                       'today' => 'report_group_edit_timeframe_today',
		                                                                       'yesterday' => 'report_group_edit_timeframe_yesterday',
		                                                                       'currweek' => 'report_group_edit_timeframe_current_week',
		                                                                       'lastweek' => 'report_group_edit_timeframe_last_week',
		                                                                       'currmonth' => 'report_group_edit_timeframe_current_month',
		                                                                       'lastmonth' => 'report_group_edit_timeframe_last_month',
		                                                                       'curryear' => 'report_group_edit_timeframe_current_year',
		                                                                       'lastyear' => 'report_group_edit_timeframe_last_year',
		                                                                       )));

        /**
		 * Report supports inner interval
		 */
		if ($this->getEntity()->getCodeTemplate()->isInnerIntervalSupported()) {
            $this->addElement('select', 'inner_interval', array('label' => 'report_group_edit_inner',
        		                                               'required' => true,
        		                                               'value' => 'custom',
        		                                               'multiOptions' => array(0 => 'report_group_edit_inner_none',
        		                                                                       5 => 'report_group_edit_inner_5min',
        		                                                                       10 => 'report_group_edit_inner_10min',
        		                                                                       30 => 'report_group_edit_inner_30min',
        		                                                                       60 => 'report_group_edit_inner_1hour',
        		                                                                       180 => 'report_group_edit_inner_3hours',
        		                                                                       360 => 'report_group_edit_inner_6hours',
        		                                                                       720 => 'report_group_edit_inner_12hours',
        		                                                                       1440 => 'report_group_edit_inner_1day',
        		                                                                       10080 => 'report_group_edit_inner_1week',
        		                                                                       20160 => 'report_group_edit_inner_2weeks',
        		                                                                       43200 => 'report_group_edit_inner_1month',
        		                                                                       129600 => 'report_group_edit_inner_3months',
        		                                                                       525600 => 'report_group_edit_inner_1year'
        		                                                                       )));
		}

		if ($this->getEntity()->getCodeTemplate()->getFormatDefault() != 'NotUserDefineable') {
    		$this->addElement('select', 'format_selected', array('label' => 'report_group_edit_format',
    		                                                     'required' => true,
    		                                                     'value' => $this->getEntity()->getFormatSelected(),
    		                                                     'multiOptions' => array(
        		                                                     'Graph' => 'report_group_edit_format_graph',
        		                                                     'Table' => 'report_group_edit_format_table',
        		                                                     'Both'  => 'report_group_edit_format_both'
    		                                                     )));

    		/**
    		 * Report supports depth limiting
    		 */
    		if ($this->getEntity()->getCodeTemplate()->isGroupDepthSupported()) {
    		    $this->addElement('select', 'group_depth_max', array('label' => 'report_group_edit_depth',
    		                                                   'required' => true,
    		                                                   'value' => $this->getEntity()->getGroupDepthMax(),
    		                                                   'multiOptions' => array(-2 => 'report_group_edit_depth_nolimit',
    		                                                                           -1 => 'report_group_edit_depth_groups',
    		                                                                           0 => 'report_group_edit_depth_groups_0',
    		                                                                           1 => 'report_group_edit_depth_groups_1',
    		                                                                           2 => 'report_group_edit_depth_groups_2',
    		                                                                           3 => 'report_group_edit_depth_groups_3',
    		                                                                           4 => 'report_group_edit_depth_groups_4',
    		                                                                           5 => 'report_group_edit_depth_groups_5',
    		                                                                           )));

    		    $this->addElement('select', 'group_depth_chart_max', array('label' => 'report_group_edit_depth_chart',
    		                                                   'required' => true,
    		                                                   'value' => $this->getEntity()->getGroupDepthChartMax(),
    		                                                   'multiOptions' => array(-2 => 'report_group_edit_depth_nolimit',
    		                                                                           -1 => 'report_group_edit_depth_groups',
    		                                                                           0 => 'report_group_edit_depth_groups_0',
    		                                                                           1 => 'report_group_edit_depth_groups_1',
    		                                                                           2 => 'report_group_edit_depth_groups_2',
    		                                                                           3 => 'report_group_edit_depth_groups_3',
    		                                                                           4 => 'report_group_edit_depth_groups_4',
    		                                                                           5 => 'report_group_edit_depth_groups_5',
    		                                                                           )));
    		}
		} else {
            $this->addElement('hidden', 'format_selected', array('value' => $this->getEntity()->getCodeTemplate()->getFormatDefault()));
		}

		$this->addElement('submit', 'form_element_submit', array('label' => 'report_group_edit_form_save',
																 'tabindex' => 20,
                                                        		 'class' => 'button',
                                                        		 'decorators' => array('ViewHelper',
                                                        		                       array(
                                                        		                             array('span' => 'HtmlTag' ),
                                                        		                             array('tag' => 'span',
                                                        		                            	  'class' => 'button green')
                                                        		                             )
                                                        		                       )
                                                        		 ));

		$this->addElement('href',
						  'form_element_cancel',
		                  array('label' => 'report_group_edit_form_cancel',
		                  		'tabindex' => 20,
		                  		'href' => (isset($this->getView()->refererUrl)) ? $this->getView()->refererUrl : null,
		                  		'data' => array('params' => array('module' => 'reports',
		                  										  'controller' => 'group',
		                  										  'action' => 'index'),
		                                        'route' => 'default',
		                  						'reset' => true ),
		                  		'decorators' => array('ViewHelper',
		                                              array(array('span' => 'HtmlTag' ),
		                                                    array('tag' => 'span',
		                                                          'class' => 'button blue'))
		                                                    )
                                )
		                 );

		$this->addDisplayGroup(array(
									'title',
									'description',
									'timeframe',
									'date_from',
									'date_to',
		                            'inner_interval',
									'report_type',
									'report_interval',
		                            'format_selected',
									'group_depth_max',
									'group_depth_chart_max' ),
							   'report_preferences' );

		$this->addDisplayGroup(array('email',
				'groups_assigned',
				'available_roles'),
				'access');

		$this->addDisplayGroup ( array ('form_element_submit', 'form_element_cancel' ), 'formbuttons' );

		$this->setDisplayGroupDecorators ( array ('FormElements', 'HtmlTag' => array ('decorator' => 'HtmlTag', 'options' => array ('tag' => 'div', 'class' => 'span-9' ) ) ) );
		$this->getDisplayGroup ( 'formbuttons' )->setDecorators ( array ('FormElements', 'HtmlTag' => array ('decorator' => 'HtmlTag', 'options' => array ('tag' => 'div', 'class' => 'buttons span-18' ) ) ) );
	}

	public function populate(array $values) {
		if (isset($values['groups_assigned']) && count($values['groups_assigned']) && $this->getElement('groups_assigned') instanceof Zend_Form_Element_MultiCheckbox) {
			foreach ($values['groups_assigned'] as $key => $value) {
				$this->getElement('groups_assigned')->addMultiOption($key, $value);
			}
		}

		if (isset($values['recepients']) && is_array($values['recepients'])
		    && (!isset($values['email']) || empty($values['email']))) {

		    $values['email'] = implode(',', $values['recepients']);
		}

		parent::populate ( $values );
	}

	public function isValid($data) {
		$valid = parent::isValid ( $data );
		if (! $valid) {

			return false;
		}

		if (isset($data['report_type']) && $data['report_type'] == 'interval'
		    && $this->getEntity()->getCodeTemplate()->getTimeframeLiveMax() === 0) {
		    return true;
		}

		$testEntity = clone $this->getEntity();

		$testEntity->fromArray($this->getValues());

		$fromDate = clone $testEntity->getDateFrom();

		$endDate = clone $testEntity->getDateTo();

		$liveMax = $testEntity->getCodeTemplate()->getTimeframeLiveMax();

		if (!$liveMax) {
		    $liveMax = 527040;
		}

		$testEntity = null;

		if ($endDate->isEarlier($fromDate)) {
		    $this->getElement('date_to')->addError('reports_group_edit_timeframe_end_before_start');
		    $this->markAsError();
		    return false;
		}

		$fromDate->addMinute($liveMax);

		if ($fromDate->isEarlier($endDate)) {
		    $this->getElement('date_to')->addError('reports_group_edit_timeframe_overlimit_error');
		    $this->markAsError();
		    return false;
		}

		return true;
	}

	public function getValues($suppressArrayNotation = false) {
		$values = parent::getValues($suppressArrayNotation );

		if (!isset($values['groups_assigned']) || $values['groups_assigned'] == null) {
		    if (!$this->getEntity()->getCodeTemplate()->isGroupSelectionSupported()) {
		        $adminUserGroups = Zend_Auth::getInstance()->getIdentity()->getGroupsAssigned();
/*
		        $mapperGroups = new Groups_Model_Mapper_Group();
		        $group = $mapperGroups->find(key($adminUserGroups));*/

			    $values['groups_assigned'] = array(key($adminUserGroups) => key($adminUserGroups));
		    } else {
		        $values['groups_assigned'] = array();
		    }
		}

		return $values;
	}
}