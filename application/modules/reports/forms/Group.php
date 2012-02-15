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

		$this->addElement('text', 'title', array ('label' => 'report_group_title', 'required' => true, 'class' => 'span-5', 'validators' => array ('len' => array ('validator' => 'StringLength', 'options' => array ('min' => 2 ) ) ) ) );
		$this->addElement('text', 'description', array ('label' => 'report_group_description', 'required' => true, 'class' => 'span-5', 'validators' => array ('len' => array ('validator' => 'StringLength', 'options' => array ('min' => 2 ) ) ) ) );

		$this->addElement('date', 'date_from', array('label' => 'report_group_date_from',
													 'required' => true,
													 'class' => 'span-5 datetimepicker',
													 'format' => Zend_Date::DATETIME_SHORT));

		$this->addElement('date', 'date_to', array('label' => 'report_group_date_to',
												   'required' => true,
												   'class' => 'span-5 datetimepicker',
												   'format' => Zend_Date::DATETIME_SHORT));

		$this->addElement('select', 'report_type', array('label' => 'report_group_report_type',
				'required' => true,
				'class' => 'span-5',
				'multiOptions' => array(
						'0' => 'report_group_report_type_manual',
						'1' => 'report_group_report_type_interval',
						)
				));

		$this->addElement('select', 'report_interval', array('label' => 'report_group_report_interval',
				'required' => true,
				'class' => 'span-5',
				'multiOptions' => array(
						'1' => 'day',
						'2' => 'week',
						'3' => 'month',
						'4' => 'year',
				)
		));
		$this->addElement ( 'textarea', 'email', array ('label' => 'report_group_email', 'required' => false, 'class' => 'span-5', 'rows' => 5, 'style' => 'height: auto !important;', 'validators' => array ('len' => array ('validator' => 'StringLength', 'options' => array ('min' => 2 ) ) ) ) );

		$decorators = $this->getElement('email')->getDecorators();

		$firstDecorators = array_slice($decorators, 0, 2, true);
		$firstDecorators[] = 'Description';

		$decorators = array_merge($firstDecorators, $decorators);

		$this->getElement('email')->setDecorators($decorators)
		                          ->setDescription('report_group_email_description');

        /**
         * Hide group tree if CAP_GLOBAL is not present
         */
		if ($this->getEntity()->getCodeTemplate()->isCapable(Reports_Model_CodeTemplate::CAP_GLOBAL)) {
    		$this->addElement('multiCheckbox', 'groups_assigned', array('label' => 'report_edit_form_group',
    											  	 			  'required' => true,
    															  'separator' => '',
    															  'registerInArrayValidator' => false));



    		$this->getElement('groups_assigned')->addErrorMessage('report_edit_form_error_group');

    		$this->addElement('hidden', 'available_roles', array('label' => 'report_edit_form_group_role',
    															 'required' => false,
    															 'class' => 'span-5',
    															 'registerInArrayValidator' => false));
		}

		/**
		 * Report supports depth limiting
		 */
		if ($this->getEntity()->getCodeTemplate()->isCapable(Reports_Model_CodeTemplate::CAP_DEPTH)) {
		    $this->addElement('select', 'max_depth', array('label' => 'report_group_edit_depth',
		                                                   'required' => true,
		                                                   'value' => -1,
		                                                   'multiOptions' => array(-1 => 'report_group_edit_depth_nolimit',
		                                                                           0 => 'report_group_edit_depth_groups',
		                                                                           1 => 'report_group_edit_depth_groups_1',
		                                                                           2 => 'report_group_edit_depth_groups_2',
		                                                                           3 => 'report_group_edit_depth_groups_3',
		                                                                           4 => 'report_group_edit_depth_groups_4',
		                                                                           5 => 'report_group_edit_depth_groups_5',
		                                                                           )));
		}

		$this->addElement('checkbox', 'date_relative', array('label' => 'report_group_edit_date_relative',
															 'required' => false));



		$this->addElement('select', 'timeframe', array('label' => 'report_group_edit_timeframe',
		                                               'required' => true,
		                                               'value' => 'custom',
		                                               'multiOptions' => array('custom' => 'report_group_edit_timeframe_custom',
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
		if ($this->getEntity()->getCodeTemplate()->isCapable(Reports_Model_CodeTemplate::CAP_INNER)) {
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

		$this->addElement('select', 'output_type', array('label' => 'report_group_edit_outputtype',
    		                                               'required' => true));

		$outputType = $this->getElement('output_type');

		if ($this->getEntity()->getCodeTemplate()->isCapable(Reports_Model_CodeTemplate::CAP_DATA)) {
		    $outputType->addMultiOption(1, 'report_group_edit_outputtype_data');
		}

		if ($this->getEntity()->getCodeTemplate()->isCapable(Reports_Model_CodeTemplate::CAP_CHART)) {
		    $outputType->addMultiOption(2, 'report_group_edit_outputtype_chart');
		}

		if (count($outputType->getMultiOptions()) == 2) {
		    $outputType->addMultiOption(3, 'report_group_edit_outputtype_both')
		               ->setValue(3);
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
									'date_relative',
									'timeframe',
									'date_from',
									'date_to',
		                            'inner_interval',
									'report_type',
									'report_interval',
									'max_depth',
		                            'output_type' ),
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
		if (isset ( $values ['groups_assigned'] ) && count ( $values ['groups_assigned'] )) {
			foreach ( $values ['groups_assigned'] as $key => $value ) {
				$this->getElement ( 'groups_assigned' )->addMultiOption ( $key, $value );
			}
		}

		parent::populate ( $values );
	}

	public function isValid($data) {
		$valid = parent::isValid ( $data );
		if (! $valid) {

			return false;
		}

		return true;
	}

	public function getValues($suppressArrayNotation = false) {
		$values = parent::getValues ( $suppressArrayNotation );

		if (! isset ( $values ['groups_assigned'] ) && $values ['groups_assigned'] == null) {
			$values ['groups_assigned'] = array ();
		}

		return $values;
	}
}