<?php

/**
 * modified by: ariane
 * modifiy date: 01/24/2017
 * download report in excel
 */
abstract class displayReportAction extends basePimReportAction {

    private $confFactory;
    private $form;
    protected $reportName = 'pim-report';
    protected $reportTitle = 'PIM Report';
    
    /**
     * Get Logger instance
     * @return Logger
     */
    protected function getLoggerInstance() {
        if (is_null($this->logger)) {
            $this->logger = Logger::getLogger('core.report.displayReportAction');
        }
        return $this->logger;
    }
    
    /**
     *
     * @return string
     */
    public function getReportName() {
        return $this->reportName;
    }

    /**
     *
     * @param string $reportName 
     */
    public function setReportName($reportName) {
        $this->reportName = $reportName;
    }

    /**
     *
     * @return string
     */
    public function getReportTitle() {
        return $this->reportTitle;
    }

    /**
     *
     * @param string $reportTitle 
     */
    public function setReportTitle($reportTitle) {
        $this->reportTitle = $reportTitle;
    }

    
    public function execute($request) {
        
        $this->setInitialActionDetails($request);

        $reportId = $request->getParameter("reportId");
        $downloadExcel = $request->getParameter("downloadExcel");
        $backRequest = $request->getParameter("backRequest");

        $reportableGeneratorService = new ReportGeneratorService();

        $sql = $request->getParameter("sql");

        $reportableService = new ReportableService();
        $this->report = $reportableService->getReport($reportId);

        if (empty($this->report)) {
            return $this->renderText(__('Invalid Report Specified'));
        }

        $useFilterField = $this->report->getUseFilterField();
        if (!$useFilterField) {

            $this->setCriteriaForm();
            if ($request->isMethod('post')) {

                $this->form->bind($request->getParameter($this->form->getName()));

                if ($this->form->isValid()) {
                    $reportGeneratorService = new ReportGeneratorService();
                    $formValues = $this->form->getValues();
                    $this->setReportCriteriaInfoInRequest($formValues);
                    $sql = $reportGeneratorService->generateSqlForNotUseFilterFieldReports($reportId, $formValues);
                }else{
                    $this->redirect($request->getReferer());
                }
            }
        } else {

            if ($request->isMethod("get")) {
                $reportGeneratorService = new ReportGeneratorService();
//                $selectedRuntimeFilterFieldList = $reportGeneratorService->getSelectedRuntimeFilterFields($reportId);

                $selectedFilterFieldList = $reportableService->getSelectedFilterFields($reportId, false);
                
                $values = $this->setValues();

//                $linkedFilterFieldIdsAndFormValues = $reportGeneratorService->linkFilterFieldIdsToFormValues($selectedRuntimeFilterFieldList, $values);
//                $runtimeWhereClauseConditionArray = $reportGeneratorService->generateWhereClauseConditionArray($linkedFilterFieldIdsAndFormValues);

                $runtimeWhereClauseConditionArray = $reportGeneratorService->generateWhereClauseConditionArray($selectedFilterFieldList, $values);
                $sql = $reportGeneratorService->generateSql($reportId, $runtimeWhereClauseConditionArray);
            }
        }

        $paramArray = array();

        if ($reportId == 1) {
            if (!isset($backRequest)) {
                $this->getUser()->setAttribute("reportCriteriaSql", $sql);
                $this->getUser()->setAttribute("parametersForListComponent", $this->setParametersForListComponent());
            }
            if (isset($backRequest) && $this->getUser()->hasAttribute("reportCriteriaSql")) {
                $sql = $this->getUser()->getAttribute("reportCriteriaSql");
                $paramArray = $this->getUser()->getAttribute("parametersForListComponent");
            }
        }


        $params = (!empty($paramArray)) ? $paramArray : $this->setParametersForListComponent();
        
        try {
            $rawDataSet = $reportableGeneratorService->generateReportDataSet($reportId, $sql);
     
             if ($downloadExcel == 1) {
                error_reporting(E_ALL);
                ini_set('display_errors', TRUE);
                ini_set('display_startup_errors', TRUE);
                date_default_timezone_set('Europe/London');

                define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

                /** Include PHPExcel */
                require_once 'Classes/PHPExcel.php';

                $objPHPExcel = new PHPExcel();

                $objPHPExcel->getProperties()->setCreator("MSU-HRAMPS")
                             ->setLastModifiedBy("MSU-HRAMPS")
                             ->setTitle("Office 2007 XLSX Test Document")
                             ->setSubject("Office 2007 XLSX Test Document")
                             ->setDescription("MSU-HRAMPS Reports")
                             ->setKeywords("office 2007 openxml php")
                             ->setCategory("Report Files");
                $style = array(
                    'alignment' => array(
                        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    )
                );
                $sheet = $objPHPExcel->getActiveSheet();
                $sheet->getDefaultStyle()->applyFromArray($style);             


                $objPHPExcel->setActiveSheetIndex(0)
                            ->mergeCells('A1:K1')
                            ->mergeCells('L1:Q1')
                            ->mergeCells('R1:T1')
                            ->mergeCells('U1:Z1')
                            ->mergeCells('AA1:AE1')
                            ->mergeCells('AF1:AH1')
                            ->mergeCells('AI1:AK1')
                            ->mergeCells('AL1:AO1')
                            ->mergeCells('AP1:AR1')
                            ->mergeCells('AS1:AU1')
                            ->mergeCells('AV1:AX1')
                            ->mergeCells('AY1:Bh1')
                            ->mergeCells('BI1:BP1')
                            ->mergeCells('BQ1:BX1')

                            ->setCellValue('A1', 'Personal')
                            ->setCellValue('L1', 'Contact Details')
                            ->setCellValue('R1', 'Dependents')
                            ->setCellValue('U1', 'Memberships')
                            ->setCellValue('AA1', 'Work Experience')
                            ->setCellValue('AF1', 'Education')

                            ->setCellValue('AI1', 'Skills')
                            ->setCellValue('AL1', 'Languages')
                            ->setCellValue('AP1', 'License')
                            ->setCellValue('AS1', 'Supervisors')
                            ->setCellValue('AV1', 'Subordinates')
                            ->setCellValue('AY1', 'Salary')
                            ->setCellValue('BI1', 'Job')
                            ->setCellValue('BQ1', 'Immigration');

                 // Initialize
                 $i = 2;
                 foreach ($rawDataSet as $rd) {
                       $i++;
                             // Validate if variable is set else it will throw undefined variable error
                            (empty($rd['employeeId'])) ? $employeeId = '' : $employeeId = $rd['employeeId'];
                            (empty($rd['employeeLastname'])) ? $employeeLastname = '' : $employeeLastname = $rd['employeeLastname'];
                            (empty($rd['employeeFirstname'])) ? $employeeFirstname = '' : $employeeFirstname = $rd['employeeFirstname'];
                            (empty($rd['employeeMiddlename'])) ? $employeeMiddlename = '' : $employeeMiddlename = $rd['employeeMiddlename'];
                            (empty($rd['empBirthday'])) ? $empBirthday = '' : $empBirthday = $rd['empBirthday'];
                            (empty($rd['nationality'])) ? $nationality = '' : $nationality = $rd['nationality'];
                            (empty($rd['empGender'])) ? $empGender = '' : $empGender = $rd['empGender'];
                            (empty($rd['maritalStatus'])) ? $maritalStatus = '' : $maritalStatus = $rd['maritalStatus'];
                            (empty($rd['biometricId'])) ? $biometricId = '' : $biometricId = $rd['biometricId'];
                            (empty($rd['religion'])) ? $religion = '' : $religion = $rd['religion'];
                            (empty($rd['bloodType'])) ? $bloodType = '' : $bloodType = $rd['bloodType'];
                            (empty($rd['address'])) ? $address = '' : $address = $rd['address'];
                            (empty($rd['homeTelephone'])) ? $homeTelephone = '' : $homeTelephone = $rd['homeTelephone'];
                            (empty($rd['mobile'])) ? $mobile = '' : $mobile = $rd['mobile'];
                            (empty($rd['workTelephone'])) ? $workTelephone = '' : $workTelephone = $rd['workTelephone'];
                            (empty($rd['workEmail'])) ? $workEmail = '' : $workEmail = $rd['workEmail'];
                            (empty($rd['otherEmail'])) ? $otherEmail = '' : $otherEmail = $rd['otherEmail'];
                            (empty($rd['ecname'][0])) ? $ecname = '' : $ecname = $rd['ecname'][0];
                            (empty($rd['ecHomeTelephone'][0])) ? $ecHomeTelephone = '' : $ecHomeTelephone = $rd['ecHomeTelephone'][0];
                            (empty($rd['ecWorkTelephone'][0])) ? $ecWorkTelephone = '' : $ecWorkTelephone = $rd['ecWorkTelephone'][0];
                            (empty($rd['ecRelationship'][0])) ? $ecRelationship = '' : $ecRelationship = $rd['ecRelationship'][0];
                            (empty($rd['ecMobile'][0])) ? $ecMobile = '' : $ecMobile = $rd['ecMobile'][0];
                            (empty($rd['dependentName'][0])) ? $dependentName = '' : $dependentName = $rd['dependentName'][0];
                            (empty($rd['dependentRelationship'][0])) ? $dependentRelationship = '' : $dependentRelationship = $rd['dependentRelationship'][0];
                            (empty($rd['dependentDateofBirth'][0])) ? $dependentDateofBirth = '' : $dependentDateofBirth = $rd['dependentDateofBirth'][0];
                            (empty($rd['edSeqNo'][0])) ? $edSeqNo = '' : $edSeqNo = $rd['edSeqNo'][0];
                            (empty($rd['subscriptionPaidBy'][0])) ? $subscriptionPaidBy = '' : $subscriptionPaidBy = $rd['subscriptionPaidBy'][0];
                            (empty($rd['subscriptionAmount'][0])) ? $subscriptionAmount = '' : $subscriptionAmount = $rd['subscriptionAmount'][0];
                            (empty($rd['membershipCurrency'][0])) ? $membershipCurrency = '' : $membershipCurrency = $rd['membershipCurrency'][0];
                            (empty($rd['subscriptionCommenceDate'][0])) ? $subscriptionCommenceDate = '' : $subscriptionCommenceDate = $rd['subscriptionCommenceDate'][0];
                            (empty($rd['subscriptionRenewalDate'][0])) ? $subscriptionRenewalDate = '' : $subscriptionRenewalDate = $rd['subscriptionRenewalDate'][0];
                            (empty($rd['expCompany'][0])) ? $expCompany = '' : $expCompany = $rd['expCompany'][0];
                            (empty($rd['expJobTitle'][0])) ? $expJobTitle = '' : $expJobTitle = $rd['expJobTitle'][0];
                            (empty($rd['expFrom'][0])) ? $expFrom = '' : $expFrom = $rd['expFrom'][0];
                            (empty($rd['expTo'][0])) ? $expTo = '' : $expTo = $rd['expTo'][0];
                            (empty($rd['expComment'][0])) ? $expComment = '' : $expComment = $rd['expComment'][0];
                            (empty($rd['skill'][0])) ? $skill = '' : $skill = $rd['skill'][0];
                            (empty($rd['skillYearsOfExperience'][0])) ? $skillYearsOfExperience = '' : $skillYearsOfExperience = $rd['skillYearsOfExperience'][0];
                            (empty($rd['skillComments'][0])) ? $skillComments = '' : $skillComments = $rd['skillComments'][0];
                            (empty($rd['langName'][0])) ? $langName = '' : $langName = $rd['langName'][0];
                            (empty($rd['langCompetency'][0])) ? $langCompetency = '' : $langCompetency = $rd['langCompetency'][0];
                            (empty($rd['langComments'][0])) ? $langComments = '' : $langComments = $rd['langComments'][0];
                            (empty($rd['langFluency'][0])) ? $langFluency = '' : $langFluency = $rd['langFluency'][0];
                            (empty($rd['empLicenseType'][0])) ? $empLicenseType = '' : $empLicenseType = $rd['empLicenseType'][0];
                            (empty($rd['empLicenseIssuedDate'][0])) ? $empLicenseIssuedDate = '' : $empLicenseIssuedDate = $rd['empLicenseIssuedDate'][0];
                            (empty($rd['empLicenseExpiryDate'][0])) ? $empLicenseExpiryDate = '' : $empLicenseExpiryDate = $rd['empLicenseExpiryDate'][0];
                            (empty($rd['supervisorFirstName'][0])) ? $supervisorFirstName = '' : $supervisorFirstName = $rd['supervisorFirstName'][0];
                            (empty($rd['supervisorLastName'][0])) ? $supervisorLastName = '' : $supervisorLastName = $rd['supervisorLastName'][0];
                            (empty($rd['supReportingMethod'][0])) ? $supReportingMethod = '' : $supReportingMethod = $rd['supReportingMethod'][0];
                            (empty($rd['subordinateFirstName'][0])) ? $subordinateFirstName = '' : $subordinateFirstName = $rd['subordinateFirstName'][0];
                            (empty($rd['subordinateLastName'][0])) ? $subordinateLastName = '' : $subordinateLastName = $rd['subordinateLastName'][0];
                            (empty($rd['subReportingMethod'][0])) ? $subReportingMethod = '' : $subReportingMethod = $rd['subReportingMethod'][0];
                            (empty($rd['salPayGrade'][0])) ? $salPayGrade = '' : $salPayGrade = $rd['salPayGrade'][0];
                            (empty($rd['salSalaryComponent'][0])) ? $salSalaryComponent = '' : $salSalaryComponent = $rd['salSalaryComponent'][0];
                            (empty($rd['salAmount'][0])) ? $salAmount = '' : $salAmount = $rd['salAmount'][0];
                            (empty($rd['salComments'][0])) ? $salComments = '' : $salComments = $rd['salComments'][0];
                            (empty($rd['salPayFrequency'][0])) ? $salPayFrequency = '' : $salPayFrequency = $rd['salPayFrequency'][0];
                            (empty($rd['salCurrency'][0])) ? $salCurrency = '' : $salCurrency = $rd['salCurrency'][0];
                            (empty($rd['ddAccountNumber'][0])) ? $ddAccountNumber = '' : $ddAccountNumber = $rd['ddAccountNumber'][0];
                            (empty($rd['ddAccountType'][0])) ? $ddAccountType = '' : $ddAccountType = $rd['ddAccountType'][0];
                            (empty($rd['ddRoutingNumber'][0])) ? $ddRoutingNumber = '' : $ddRoutingNumber = $rd['ddRoutingNumber'][0];
                            (empty($rd['ddAmount'][0])) ? $ddAmount = '' : $ddAmount = $rd['ddAmount'][0];
                            (empty($rd['empContStartDate'][0])) ? $empContStartDate = '' : $empContStartDate = $rd['empContStartDate'][0];
                            (empty($rd['empContEndDate'][0])) ? $empContEndDate = '' : $empContEndDate = $rd['empContEndDate'][0];
                            (empty($rd['empJobTitle'][0])) ? $empJobTitle = '' : $empJobTitle = $rd['empJobTitle'][0];
                            (empty($rd['empEmploymentStatus'][0])) ? $empEmploymentStatus = '' : $empEmploymentStatus = $rd['empEmploymentStatus'][0];
                            (empty($rd['empJobCategory'][0])) ? $empJobCategory = '' : $empJobCategory = $rd['empJobCategory'][0];
                            (empty($rd['empJoinedDate'][0])) ? $empJoinedDate = '' : $empJoinedDate = $rd['empJoinedDate'][0];
                            (empty($rd['empSubUnit'][0])) ? $empSubUnit = '' : $empSubUnit = $rd['empSubUnit'][0];
                            (empty($rd['empLocation'][0])) ? $empLocation = '' : $empLocation = $rd['empLocation'][0];
                            (empty($rd['empPassportNo'][0])) ? $empPassportNo = '' : $empPassportNo = $rd['empPassportNo'][0];
                            (empty($rd['empPassportIssuedDate'][0])) ? $empPassportIssuedDate = '' : $empPassportIssuedDate = $rd['empPassportIssuedDate'][0];
                            (empty($rd['empPassportExpiryDate'][0])) ? $empPassportExpiryDate = '' : $empPassportExpiryDate = $rd['empPassportExpiryDate'][0];
                            (empty($rd['empPassportEligibleStatus'][0])) ? $empPassportEligibleStatus = '' : $empPassportEligibleStatus = $rd['empPassportEligibleStatus'][0];
                            (empty($rd['empPassportIssuedBy'][0])) ? $empPassportIssuedBy = '' : $empPassportIssuedBy = $rd['empPassportIssuedBy'][0];
                            (empty($rd['empPassportEligibleReviewDate'][0])) ? $empPassportEligibleReviewDate = '' : $empPassportEligibleReviewDate = $rd['empPassportEligibleReviewDate'][0];
                            (empty($rd['empPassportComments'][0])) ? $empPassportComments = '' : $empPassportComments = $rd['empPassportComments'][0];
                            (empty($rd['documentType'][0])) ? $documentType = '' : $documentType = $rd['documentType'][0];


                    $objPHPExcel->setActiveSheetIndex(0)
                            ->setCellValue('A'.$i,$employeeId)
                            ->setCellValue('B'.$i,$employeeLastname)
                            ->setCellValue('C'.$i,$employeeFirstname)
                            ->setCellValue('D'.$i,$employeeMiddlename)
                            ->setCellValue('E'.$i,$empBirthday)
                            ->setCellValue('F'.$i,$nationality)
                            ->setCellValue('G'.$i,$empGender)
                            ->setCellValue('H'.$i,$maritalStatus)
                            ->setCellValue('I'.$i,$biometricId)
                            ->setCellValue('J'.$i,$religion)
                            ->setCellValue('K'.$i,$bloodType)
                            ->setCellValue('L'.$i,$address)
                            ->setCellValue('M'.$i,$homeTelephone)
                            ->setCellValue('N'.$i,$mobile)
                            ->setCellValue('O'.$i,$workTelephone)
                            ->setCellValue('P'.$i,$workEmail)
                            ->setCellValue('Q'.$i,$otherEmail)
                            ->setCellValue('R'.$i,$ecname)
                            ->setCellValue('S'.$i,$ecHomeTelephone)
                            ->setCellValue('T'.$i,$ecWorkTelephone)
                            ->setCellValue('U'.$i,$ecRelationship)
                            ->setCellValue('V'.$i,$ecMobile)
                            ->setCellValue('W'.$i,$dependentName)
                            ->setCellValue('X'.$i,$dependentRelationship)
                            ->setCellValue('Y'.$i,$dependentDateofBirth)
                            ->setCellValue('Z'.$i,$edSeqNo)
                            ->setCellValue('AA'.$i,$subscriptionPaidBy)
                            ->setCellValue('AB'.$i,$subscriptionAmount)
                            ->setCellValue('AC'.$i,$membershipCurrency)
                            ->setCellValue('AD'.$i,$subscriptionCommenceDate)
                            ->setCellValue('AE'.$i,$subscriptionRenewalDate)
                            ->setCellValue('AF'.$i,$expCompany)
                            ->setCellValue('AG'.$i,$expJobTitle)
                            ->setCellValue('AH'.$i,$expFrom)
                            ->setCellValue('AI'.$i,$expTo)
                            ->setCellValue('AJ'.$i,$expComment)
                            ->setCellValue('AN'.$i,$skill)
                            ->setCellValue('AO'.$i,$skillYearsOfExperience)
                            ->setCellValue('AP'.$i,$skillComments)
                            ->setCellValue('AQ'.$i,$langName)
                            ->setCellValue('AR'.$i,$langCompetency)
                            ->setCellValue('AS'.$i, $supervisorFirstName)
                            ->setCellValue('AT'.$i, $supervisorLastName)
                            ->setCellValue('AU'.$i, $supReportingMethod)

                            ->setCellValue('AV'.$i, $subordinateFirstName)
                            ->setCellValue('AW'.$i, $subordinateLastName)
                            ->setCellValue('AX'.$i, $subReportingMethod)

                            // ->setCellValue('AS'.$i, $langComments)
                            // ->setCellValue('AT'.$i, $langFluency)
                            // ->setCellValue('AU'.$i, $empLicenseType)
                            // ->setCellValue('AV'.$i, $empLicenseIssuedDate)
                            // ->setCellValue('AW'.$i, $empLicenseExpiryDate)

                            ->setCellValue('AY'.$i, $salPayGrade)
                            ->setCellValue('AZ'.$i, $salSalaryComponent)
                            ->setCellValue('BA'.$i, $salAmount)
                            ->setCellValue('BB'.$i, $salComments)
                            ->setCellValue('BC'.$i, $salPayFrequency)
                            ->setCellValue('BD'.$i, $salCurrency)
                            ->setCellValue('BE'.$i, $ddAccountNumber)
                            ->setCellValue('BF'.$i, $ddAccountType)
                            ->setCellValue('BG'.$i, $ddRoutingNumber)
                            ->setCellValue('BH'.$i, $ddAmount)
                            ->setCellValue('BI'.$i, $empContStartDate)
                            ->setCellValue('BJ'.$i, $empContEndDate)
                            ->setCellValue('BK'.$i, $empJobTitle)
                            ->setCellValue('BL'.$i, $empEmploymentStatus)
                            ->setCellValue('BM'.$i, $empJobCategory)
                            ->setCellValue('BN'.$i, $empJoinedDate)
                            ->setCellValue('BO'.$i, $empSubUnit)
                            ->setCellValue('BP'.$i, $empLocation)
                            ->setCellValue('BQ'.$i, $empPassportNo)
                            ->setCellValue('BR'.$i, $empPassportIssuedDate)
                            ->setCellValue('BS'.$i, $empPassportExpiryDate)
                            ->setCellValue('BT'.$i, $empPassportEligibleStatus)
                            ->setCellValue('BU'.$i, $empPassportIssuedBy)
                            ->setCellValue('BV'.$i, $empPassportEligibleReviewDate)
                            ->setCellValue('BW'.$i, $empPassportComments)
                            ->setCellValue('BX'.$i, $documentType);
                    }   
                //  Auto Size Cells
                for($col = 'A'; $col !== 'BY'; $col++) {
                    $objPHPExcel->getActiveSheet()
                        ->getColumnDimension($col)
                        ->setAutoSize(true);
                }          

                $objPHPExcel->setActiveSheetIndex(0)
                            ->setCellValue('A2', 'Employee ID')
                            ->setCellValue('B2', 'Employee Last Name')
                            ->setCellValue('C2', 'Employee First Name')
                            ->setCellValue('D2', 'Employee Middle Name')
                            ->setCellValue('E2', 'Date of Birth')
                            ->setCellValue('F2', 'Nationality')
                            ->setCellValue('G2', 'Gender')
                            ->setCellValue('H2', 'Marital Status')
                            ->setCellValue('I2', 'Biometric Id')
                            ->setCellValue('J2', 'Religion')
                            ->setCellValue('K2', 'Blood Type')
                            ->setCellValue('L2', 'Address')
                            ->setCellValue('M2', 'Home Telephone')
                            ->setCellValue('N2', 'Mobile')
                            ->setCellValue('O2', 'Work Telephone')
                            ->setCellValue('P2', 'Work Email')
                            ->setCellValue('Q2', 'Other Email')
                            ->setCellValue('R2', 'Name')
                            ->setCellValue('S2', 'Relationship')
                            ->setCellValue('T2', 'Date of Birth ')
                            ->setCellValue('U2', 'Membership')
                            ->setCellValue('V2', 'Subscription Paid By')
                            ->setCellValue('W2', 'Subscription Amount')
                            ->setCellValue('X2', 'Currency')
                            ->setCellValue('Y2', 'Subscription Commence Date')
                            ->setCellValue('Z2', 'Subscription Renewal Date')
                            ->setCellValue('AA2', 'Company')
                            ->setCellValue('AB2', 'Job Title')
                            ->setCellValue('AC2', 'From')
                            ->setCellValue('AD2', 'To')
                            ->setCellValue('AE2', 'Comment')
                            ->setCellValue('AF2', 'Level')
                            ->setCellValue('AG2', 'Year')
                            ->setCellValue('AH2', 'Score')
                            ->setCellValue('AI2', 'Skill')
                            ->setCellValue('AJ2', 'Years of Experience')
                            ->setCellValue('AK2', 'Comments')
                            ->setCellValue('AL2', 'Language')
                            ->setCellValue('AM2', 'Competency')
                            ->setCellValue('AN2', 'Comments')
                            ->setCellValue('AO2', 'Fluency')
                            ->setCellValue('AP2', 'License Type')
                            ->setCellValue('AQ2', 'Issued Date')
                            ->setCellValue('AR2', 'Expiry Date')
                            ->setCellValue('AS2', 'First Name')
                            ->setCellValue('AT2', 'Last Name')
                            ->setCellValue('AU2', 'Reporting Method')
                            ->setCellValue('AV2', 'First Name')
                            ->setCellValue('AW2', 'Last Name')
                            ->setCellValue('AX2', 'Reporting Method')

                            ->setCellValue('AY2', 'Pay Grade')
                            ->setCellValue('AZ2', 'Salary Component')
                            ->setCellValue('BA2', 'Amount')
                            ->setCellValue('BB2', 'Comments')
                            ->setCellValue('BC2', 'Pay Frequency')
                            ->setCellValue('BD2', 'Currency')
                            ->setCellValue('BE2', 'Direct Deposit Account Number')
                            ->setCellValue('BF2', 'Direct Deposit Account Type')
                            ->setCellValue('BG2', 'Direct Deposit Routing Number')
                            ->setCellValue('BH2', 'Direct Deposit Amount')
                            ->setCellValue('BI2', 'Contract Start Date')
                            ->setCellValue('BJ2', 'Contract End Date')
                            ->setCellValue('BK2', 'Job Title')
                            ->setCellValue('BL2', 'Employment Status')
                            ->setCellValue('BM2', 'Job Category')
                            ->setCellValue('BN2', 'Joined Date')
                            ->setCellValue('BO2', 'Sub Unit')
                            ->setCellValue('BP2', 'Location')
                            ->setCellValue('BQ2', 'Number')
                            ->setCellValue('BR2', 'Issued Date')
                            ->setCellValue('BS2', 'Expiry Date')
                            ->setCellValue('BT2', 'Eligibility Status')
                            ->setCellValue('BU2', 'Issued By')
                            ->setCellValue('BV2', 'Eligibility Review Date')
                            ->setCellValue('BW2', 'Comments')
                            ->setCellValue('BX2', 'Document Type');

                $objPHPExcel->getActiveSheet()->setTitle('Report');


                // Set active sheet index to the first sheet, so Excel opens this as the first sheet
                $objPHPExcel->setActiveSheetIndex(0);

                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="'. $this->report->getName().'.xls"');
                header('Cache-Control: max-age=0');
                // If you're serving to IE 9, then the following may be needed
                header('Cache-Control: max-age=1');

                // If you're serving to IE over SSL, then the following may be needed
                header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
                header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
                header ('Pragma: public'); // HTTP/1.0

                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
                $objWriter->save('php://output');

            // Uncomment This if You Want To Test View The Gathered Data
            // echo '<pre>';

            // print_r($rawDataSet);
            // echo '</pre>';
            // die();

            } // end of if statement    

        } catch (Exception $e) {
            $this->getLoggerInstance()->error($e->getMessage(), $e);
            $this->getUser()->setFlash(displayMessageAction::MESSAGE_HEADING, __('Report could not be generated'), false);
            $this->getUser()->setFlash('error.nofade', __('Please run the report again.'), false);
            $this->forward('core', 'displayMessage');
        }
        
        $dataSet = self::escapeData($rawDataSet);
        
        $headerGroups = $reportableGeneratorService->getHeaderGroups($reportId);

        $this->setConfigurationFactory();
        $configurationFactory = $this->getConfFactory();
        $configurationFactory->setHeaderGroups($headerGroups);

        if ($reportId == 3) {
            if (empty($dataSet[0]['employeeName']) && $dataSet[0]['totalduration'] == 0) {
                $dataSet = null;
            }
        }

        ohrmListComponent::setConfigurationFactory($configurationFactory);

        $this->setListHeaderPartial();

        ohrmListComponent::setListData($dataSet);

        $this->parmetersForListComponent = $params;
        
        $this->initilizeDataRetriever($configurationFactory, $reportableGeneratorService, 'generateReportDataSet', array($reportId, $sql));
    }

    abstract public function setParametersForListComponent();

    abstract public function setConfigurationFactory();

    abstract public function setListHeaderPartial();

    abstract public function setValues();
    
    abstract public function setInitialActionDetails($request);

    public function getConfFactory() {

        return $this->confFactory;
    }

    public function setConfFactory(ListConfigurationFactory $configurationFactory) {

        $this->confFactory = $configurationFactory;
    }

    public function setReportCriteriaInfoInRequest($formValues) {
        
    }

    public function setCriteriaForm() {
        
    }

    public function setForm($form) {
        $this->form = $form;
    }
       
    public function initilizeDataRetriever(ohrmListConfigurationFactory $configurationFactory, BaseService $dataRetrievalService, $dataRetrievalMethod, array $dataRetrievalParams) {
        $dataRetriever = new ExportDataRetriever();
        $dataRetriever->setConfigurationFactory($configurationFactory);
        $dataRetriever->setDataRetrievalService($dataRetrievalService);
        $dataRetriever->setDataRetrievalMethod($dataRetrievalMethod);
        $dataRetriever->setDataRetrievalParams($dataRetrievalParams);

        $this->getUser()->setAttribute('persistant.exportDataRetriever', $dataRetriever);
        $this->getUser()->setAttribute('persistant.exportFileName', $this->getReportName());
        $this->getUser()->setAttribute('persistant.exportDocumentTitle', $this->getReportTitle());
        $this->getUser()->setAttribute('persistant.exportDocumentDescription', 'Generated at ' . date('Y-m-d H:i'));
    }
    
    public function escapeData($data) {
        if (is_array($data)) {
            $escapedArray = array();
            foreach ($data as $key => $rawData) {
                $escapedArray[$key] = self::escapeData($rawData);
            }
            return $escapedArray;
        } else {
            return htmlspecialchars($data);
        } 
    }

}