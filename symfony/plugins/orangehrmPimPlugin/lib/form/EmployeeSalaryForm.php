<?php

/**
 * Form class for employee salary detail
 */
class EmployeeSalaryForm extends BaseForm {

    public $fullName;
    private $currencyService;
    public $havePayGrades = false;
    private $payGrades;
    private $currencies;
    private $payPeriods;

    /**
     * Get CurrencyService
     * @returns CurrencyService
     */
    public function getCurrencyService() {
        if (is_null($this->currencyService)) {
            $this->currencyService = new CurrencyService();
        }
        return $this->currencyService;
    }

    /**
     * Set CurrencyService
     * @param CurrencyService $currencyService
     */
    public function setCurrencyService(CurrencyService $currencyService) {
        $this->currencyService = $currencyService;
    }

    public function configure() {
         $this->salaryPermissions = $this->getOption('salaryPermissions');
         
        $empNumber = $this->getOption('empNumber');
        $employee = $this->getOption('employee');
        $this->fullName = $employee->getFullName();

        $this->payGrades = $this->_getPayGrades();
        $this->currencies = $this->_getCurrencies();
        $this->payPeriods = $this->_getPayPeriods();

        $widgets = array('emp_number' => new sfWidgetFormInputHidden(array(), array('value' => $empNumber)));
        $validators = array('emp_number' => new sfValidatorString(array('required' => true)));

        if ($this->salaryPermissions->canRead()) {

            $salaryWidgets = $this->getSalaryWidgets();
            $salaryValidators = $this->getSalaryValidators();

            if (!($this->salaryPermissions->canUpdate() || $this->salaryPermissions->canCreate()) ) {
                foreach ($salaryWidgets as $widgetName => $widget) {
                    $widget->setAttribute('disabled', 'disabled');
                }
            }
            $widgets = array_merge($widgets, $salaryWidgets);
            $validators = array_merge($validators, $salaryValidators);
        }

        $this->setWidgets($widgets);
        $this->setValidators($validators);

        $this->widgetSchema->setNameFormat('salary[%s]');

        // set up your post validator method
        $this->validatorSchema->setPostValidator(
                new sfValidatorCallback(array(
                    'callback' => array($this, 'postValidate')
                ))
        );
    }

    /*
     * Tis fuction will return the widgets of the form
     *
     ** modified by: ariane
     ** modified date: 12-20-2016
     **/

    public function getSalaryWidgets() {
        $widgets = array();

        //creating widgets
        // Note: Widget names were kept from old non-symfony version
        $widgets['id'] = new sfWidgetFormInputHidden();
        $widgets['currency_id'] = new sfWidgetFormSelect(array('choices' => $this->currencies));
        // $widgets['salamount'] = new sfWidgetFormInputText(array(), array('disabled' => 'disabled'));
        $widgets['basic_salary'] = new sfWidgetFormInputText(array(),array('disabled' => 'disabled'));
        // $widgets['payperiod_code'] = new sfWidgetFormSelect(array('choices' => $this->payPeriods));
        // $widgets['salary_component'] = new sfWidgetFormInputText();
        $widgets['effectivityDate'] = new ohrmWidgetDatePicker(array(), array('id' => 'effectivity_date'));
        $widgets['comments'] = new sfWidgetFormTextArea();
        $widgets['set_direct_debit'] = new sfWidgetFormInputCheckbox(array(), array('value' => 'on'));

        if (count($this->payGrades) > 0) {
            $this->havePayGrades = true;
            $widgets['sal_grd_code'] = new sfWidgetFormSelect(array('choices' => $this->payGrades));
        } else {
            $widgets['sal_grd_code'] = new sfWidgetFormInputHidden();
        }

        // Remove default options from list validated against
        //unset($this->payGrades['']);
        unset($this->currencies['']);
        unset($this->payPeriods['']);

        return $widgets;
    }

    /*
     * Tis fuction will return the form validators
     *
     ** modified by: ariane
     ** modified date: 12-20-2016
     */

    public function getSalaryValidators() {

        $inputDatePattern = sfContext::getInstance()->getUser()->getDateFormat();

        $validators = array(
            'id' => new sfValidatorNumber(array('required' => false, 'min' => 0)),
            'currency_id' => new sfValidatorChoice(array('required' => true, 'choices' => array_keys($this->currencies))),
            'basic_salary' => new sfValidatorNumber(array('required' => true, 'trim' => true, 'min' => 0, 'max' => 999999999.99)),
            // 'payperiod_code' => new sfValidatorChoice(array('required' => false, 'choices' => array_keys($this->payPeriods))),
            // 'salary_component' => new sfValidatorString(array('required' => false, 'max_length' => 100)),
            'effectivityDate' => new ohrmDateValidator(array('date_format'=>$inputDatePattern,'required'=>false), 
                array ('invalid'=> 'Date format should be' . $inputDatePattern)),
            'comments' => new sfValidatorString(array('required' => false, 'max_length' => 255)),
            'set_direct_debit' => new sfValidatorString(array('required' => false)),
            // 'salamount' => new sfValidatorString(array('required' => false)),
        );
        
        if ($this->havePayGrades) {
            $validator = array('sal_grd_code' => new sfValidatorChoice(array('required' => false, 'choices' => array_keys($this->payGrades))));
        } else {
            // We do not expect a value. Validate as an empty string
            $validator = array('sal_grd_code' => new sfValidatorString(array('required' => false, 'max_length' => 10)));
        }
        
        $validators = array_merge($validators, $validator);

        return $validators;
    }

    public function postValidate($validator, $values) {
        $service = new PayGradeService();

        $salaryGrade = $values['sal_grd_code'];

        $salary = $values['basic_salary'];

        if (!empty($salaryGrade)) {

            $salaryDetail = $service->getCurrencyByCurrencyIdAndPayGradeId($values['currency_id'], $salaryGrade);


            if (empty($salaryDetail)) {

                $message = sfContext::getInstance()->getI18N()->__('Invalid Salary Grade.');
                $error = new sfValidatorError($validator, $message);
                throw new sfValidatorErrorSchema($validator, array('' => $error));
            } else if ((!empty($salaryDetail->minSalary) && ($salary < $salaryDetail->minSalary)) ||
                    (!empty($salaryDetail->maxSalary) && ($salary > $salaryDetail->maxSalary))) {

                $message = sfContext::getInstance()->getI18N()->__('Salary should be within min and max');
                $error = new sfValidatorError($validator, $message);
                throw new sfValidatorErrorSchema($validator, array('basic_salary' => $error));
            }
        } else {
            $values['sal_grd_code'] = null;
        }

        // cleanup cmbPayPeriod
        // $payPeriod = $values['payperiod_code'];
        // if ($payPeriod == '0' || $payPeriod = '') {
        //     $values['payperiod_code'] = null;
        // }

        // Convert salary to a string. Since database field is a string field.
        // Otherwise, it may be converted to a string using scientific notation when encrypting.
        //        
        // Remove trailing zeros - will always have decimal point, so 
        // only trailing decimals are removed.
        $formattedSalary = rtrim(sprintf("%.2F", $salary), '0');

        // Remove decimal point (if it is the last char).
        $formattedSalary = rtrim($formattedSalary, '.');

        $values['basic_salary'] = $formattedSalary;

        return $values;
    }

    /**
     * Get EmployeeSalary object
     *
     ** modified by: ariane
     ** modified date: 12-20-2016
     */
    public function getSalary() {

        $id = $this->getValue('id');

        $empSalary = false;

        if (!empty($id)) {
            $empSalary = Doctrine::getTable('EmployeeSalary')->find($id);
        }

        if ($empSalary === false) {
            $empSalary = new EmployeeSalary();
        }

        $empSalary->setEmpNumber($this->getValue('emp_number'));
        $empSalary->setPayGradeId($this->getValue('sal_grd_code'));
        $empSalary->setCurrencyCode($this->getValue('currency_id'));
        // $empSalary->setPayPeriodId($this->getValue('payperiod_code'));
        // $empSalary->setSalaryName($this->getValue('salary_component'));
        $empSalary->setAmount($this->getValue('basic_salary'));
        $empSalary->setEffectivityDate($this->getValue('effectivityDate'));
        $empSalary->setNotes($this->getValue('comments'));
        // $empSalary->setSalAmount($this->getValue('amount'));
        
        $setDirectDebit = $this->getValue('set_direct_debit');
        if ($setDirectDebit) {
            
        }

        return $empSalary;
    }

    private function _getPayGrades() {
        $choices = array();

        $service = new PayGradeService();
        $payGrades = $service->getPayGradeList();

        if (count($payGrades) > 0) {
            $choices = array('' => '-- ' . __('Select') . ' --');

            foreach ($payGrades as $payGrade) {
                $choices[$payGrade->getId()] = $payGrade->getName();
            }
        }
        return $choices;
    }

    /**
     * Get Pay Periods as array.
     * 
     * @return Array (empty array if no pay periods defined).
     */
    private function _getPayPeriods() {
        $payPeriods = Doctrine::getTable('Payperiod')->findAll();

        foreach ($payPeriods as $payPeriod) {
            $choices[$payPeriod->getCode()] = $payPeriod->getName();
        }

        asort($choices);

        $choices = array('' => '-- ' . __('Select') . ' --') + $choices;

        return $choices;
    }

    private function _getCurrencies() {
        $currencies = $this->getCurrencyService()->getCurrencyList();
        $choices = array('' => '-- ' . __('Select') . ' --');
        $salaryProperties = array('salamount');

        foreach ($currencies as $currency) {
            $choices[$currency->getCurrencyId()] = $currency->getCurrencyName();
            if (in_array($choices, $this->allowedVacancyList)
                    || !$predefined) {
                $choices[$currency->getCurrencyId().'__inline_option_'] = $vacancy['salamount'];
             }
        }
        return $choices;
    }

}

