<?php

/**
 * Description of getTotalEmployees
 * added by: Ariane Adajar
 * added on: 2/6/2017
 */
class getPayslipArchiveJsonAction extends sfAction {
  
  /**
   *
   * @param <type> $request
   * @return <type>
   */
  public function execute($request) {

    $this->setLayout(false);
    sfConfig::set('sf_web_debug', false);
    sfConfig::set('sf_debug', false);

    if ($this->getRequest()->isXmlHttpRequest()) {
      $this->getResponse()->setHttpHeader('Content-Type', 'application/json; charset=utf-8');
    }

    // create the connection
    $yml    = sfYaml::load(sfConfig::get('sf_config_dir').'/databases.yml');
    $params = $yml['all']['doctrine']['param'];
    $dbh    = new PDO($params['dsn'], $params['username'], $params['password']);

    $array_fields = array();

    $statement = $dbh->prepare("SELECT p.employee_id, 
                  TRIM(CONCAT(emp_lastname, ', ', emp_firstname, ' ', emp_middle_name)) AS emp_name,
                  CONCAT(DATE_FORMAT(p.pay_fromdate, '%m/%d/%Y'), ' - ',   
                         DATE_FORMAT(p.pay_todate, '%m/%d/%Y')) payroll_period, 
                    DATE_FORMAT(p.pay_date, '%m/%d/%Y') pay_date,
                    TRIM(CONCAT(emp_lastname, '_', emp_firstname,'_',DATE_FORMAT(p.pay_fromdate, '%m-%d-%Y'), '_', DATE_FORMAT(p.pay_todate, '%m-%d-%Y'))) filename,
                  p.file_data 
                  FROM hs_hr_emp_payslip p 
                  LEFT JOIN hs_hr_employee e 
                  ON (p.`employee_id` = e.`employee_id`)");
    $statement->execute();
    $employee_data = $statement->fetchAll();
    foreach ($employee_data as $ed) {
      $array_fields[] =  array(
                    'employee_id' =>  $ed['employee_id'] ,
                    'emp_name'  =>  $ed['emp_name'],
                    'payroll_period'  =>  $ed['payroll_period'],
                    'pay_date'  =>  $ed['pay_date'],
                    'filename'  =>  $ed['filename'],
                    'file_data' => $ed['file_data']
                  );
    }
    
    return $this->renderText(json_encode($array_fields));



  }
}

?>
