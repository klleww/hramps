<?php
/**
*modified by: ariane
*modified date: 12-13-16
*/
class PayGradeCurrencyForm extends BaseForm {
	
	private $payGradeService;
	public $payGradeId;

	public function getPayGradeService() {
		if (is_null($this->payGradeService)) {
			$this->payGradeService = new PayGradeService();
			$this->payGradeService->setPayGradeDao(new PayGradeDao());
		}
		return $this->payGradeService;
	}
	
	public function configure() {

		$this->payGradeId = $this->getOption('payGradeId');
		
		$this->setWidgets(array(
		    'currencyId' => new sfWidgetFormInputHidden(),
		    'payGradeId' => new sfWidgetFormInputHidden(),
		    // 'levelName' => new sfWidgetFormInputText(),
		    'salaryAmount' => new sfWidgetFormInputText(),
		    'currencyName' => new sfWidgetFormInputText(),
		    'minSalary' => new sfWidgetFormInputText(),
		    'maxSalary' => new sfWidgetFormInputText(),
		));

		$this->setValidators(array(
		    'currencyId' => new sfValidatorString(array('required' => false)),
		    'payGradeId' => new sfValidatorNumber(array('required' => false)),
		    // 'levelName' => new sfValidatorString(array('required' => true)),
		    'salaryAmount' => new sfValidatorNumber(array('required' => false)),
		    'currencyName' => new sfValidatorString(array('required' => true)),
		    'minSalary' => new sfValidatorNumber(array('required' => false)),
		    'maxSalary' => new sfValidatorNumber(array('required' => false)),
		));

		$this->widgetSchema->setNameFormat('payGradeCurrency[%s]');		
	}
	
	public function save(){
		
		$currencyId = $this->getValue('currencyId');
		$currencyName = $this->getValue('currencyName');
		$temp = explode(" - ", trim($currencyName));
		
		if(!empty ($currencyId)){
			$currency = $this->getPayGradeService()->getCurrencyByCurrencyIdAndPayGradeId($currencyId, $this->payGradeId);
		} else {
			$currency = new PayGradeCurrency();
		}
		// $currency->levelName = $this->getValue('levelName');
		$currency->salaryAmount = $this->getValue('salaryAmount');
		$currency->setPayGradeId($this->payGradeId);
		$currency->setCurrencyId($temp[0]);
		$currency->setMinSalary(sprintf("%01.2f", $this->getValue('minSalary')));
		$currency->setMaxSalary(sprintf("%01.2f", $this->getValue('maxSalary')));
		$currency->save();
		return $this->payGradeId;
	}
	
}

?>