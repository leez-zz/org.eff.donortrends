<?php

class CRM_Donortrends_Form_Trends extends CRM_Core_Form {
  
  function preProcess(){
    $formValues = $this->get('formValues');
    if(!empty($formValues)) {
      $this->postProcess();
    } 
  }
  
  public function buildQuickForm(){
   
    $report_type = array(
      '0' => 'Overview',
      '1' => 'New',
      '2' => 'Lapsed',
      '3' => 'Upgraded',
      '4' => 'Downgraded',
      '5' => 'Maintained',
    );

    $element = $this->add('select',
      'report_type',
      'Report Type',
      $report_type,
      true
    );
 
    $curr_year = date("Y");
    $target_year = array(
      $curr_year => $curr_year,
      $curr_year-1 => $curr_year-1,
      $curr_year-2 => $curr_year-2,
      $curr_year-3 => $curr_year-3,
    );

    $element = $this->add('select',
      'target_year',
      'Target Year',
      $target_year,
      true
    );

    //$array_groups = CRM_Core_PseudoConstant::allGroup();
    $array_groups = array(''=>'');
    $array_groups += CRM_Core_PseudoConstant::allGroup();
    $element = $this->add('select',
      'group',
      'Group',
      $array_groups,
      false
    );

    $this->addButtons(array(
      array(
        'type' => 'refresh',
        'name' => 'SHOW ME THE MONEY',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'submit',
        'name' => 'EXPORT ME THE MONEY',
      ),
    ));  
  }  

  public function postProcess( ){
    $params = $this->exportValues();
    
    if(empty($params)){
      $params = $this->get('formValues');
    }
    else{

      if (!isset($params['_qf_Trends_submit'])) {
        $this->set('formValues', $params);
        $this->controller->resetPage($this->_name);
        return;
      }
    }
    //crm_core_error::debug('test', 'test');
    //exit;

    //overview 
    if($params['report_type'] == '0'){
      $report_name = 'overview';
      $titles = array("", "NEW", "LAPSED", "UPGRADED", "DOWNGRADED", "MAINTAINED");
      $contents = array();
      
      //new donors prep query: this one is easy lemon squeezy - get donors whose first contribution date is in the specified year
      $query_string = "SELECT cc.contact_id, MIN(cc.receive_date) AS receive_date FROM civicrm_contribution cc";  
      if($params['group']){
        $group_filter = (int)$params['group'];
        $query_string .= " LEFT JOIN civicrm_group_contact on cc.contact_id = civicrm_group_contact.contact_id
                          WHERE civicrm_group_contact.group_id = $group_filter";
      }  
      $query_string .= " GROUP BY contact_id HAVING receive_date >= %1 and receive_date < %2";  

      //lapsed/upgraded/downgraded/maintained donors prep query
      $query_string_2 = "SELECT cc.contact_id, SUM(total_amount) as total FROM civicrm_contribution cc";
      $query_string_where = " WHERE cc.receive_date >= %1 and cc.receive_date < %2";
      
      if($params['group']){
        $group_filter = (int)$params['group'];
        $query_string_2 .= " LEFT JOIN civicrm_group_contact ON cc.contact_id = civicrm_group_contact.contact_id";
        $query_string_where .= " AND civicrm_group_contact.group_id = $group_filter";
      }
      $query_string_2 .= $query_string_where;
      $query_string_2 .= " GROUP BY contact_id";  

      $curr_year = date("Y");
      for($year=$curr_year; $year>=$curr_year-3; $year--){
    
        //set up and format dates
        $start = new DateTime($year.'-01-01');
        $end = new DateTime($year.'-01-01');
        $end->add(new DateInterval('P1Y'));
        $start = $start->format('Y-m-d H:i:s');
        $end = $end->format('Y-m-d H:i:s');
        $start_prev = new DateTime($year.'-01-01');
        $end_prev = new DateTime($year.'-01-01');
        $start_prev->sub(new DateInterval('P1Y'));
        $start_prev = $start_prev->format('Y-m-d H:i:s');
        $end_prev = $end_prev->format('Y-m-d H:i:s');
                        
        //new donors: run previously-prepped query string with appropriate dates                    
        $dates = array(1 => array($start, 'String'), 2 => array($end, 'String'));
        $result = CRM_Core_DAO::executeQuery($query_string, $dates);
                        
        $new = 0;
        while($result->fetch()){ 
          $new++;
        }
    
        //lapsed/upgraded/downgraded/maintained donors: run previously-prepped query strings with appropriate dates
        $dates = array(1 => array($start, 'String'), 2 => array($end, 'String'));
        $result = CRM_Core_DAO::executeQuery($query_string_2, $dates);
        $dates_prev = array(1 => array($start_prev, 'String'), 2 => array($end_prev, 'String'));
        $result_prev = CRM_Core_DAO::executeQuery($query_string_2, $dates_prev);
        $lapsed = 0;
        $upgraded = 0;
        $downgraded = 0;
        $maintained = 0;
        $result_array = array();
        $result_prev_array = array();
        //set up two arrays for comparing
        while($result->fetch()){ 
          $result_array[$result->contact_id] = $result->total;
        }
        while($result_prev->fetch()){ 
          $result_prev_array[$result_prev->contact_id] = $result_prev->total;
        }
    
        foreach($result_prev_array as $contact_id => $total){
          if(!array_key_exists($contact_id,$result_array)){
            $lapsed++;
          }
        }
    
        foreach($result_array as $contact_id => $total){
          if(array_key_exists($contact_id,$result_prev_array) && $total > $result_prev_array[$contact_id]){
            $upgraded++;
          }
          elseif(array_key_exists($contact_id,$result_prev_array) && $total < $result_prev_array[$contact_id]){
            $downgraded++;
          }
          elseif(array_key_exists($contact_id,$result_prev_array) && $total == $result_prev_array[$contact_id]){
            $maintained++;
            //echo $contact_id. ', ';
          }
        }
        $content = array($year, $new, $lapsed, $upgraded, $downgraded, $maintained);
        $contents[] = $content;
      }//end for $year
    }//end overview

    //new
    elseif($params['report_type'] == '1'){
      $report_name = 'new';
      $titles = array("CONTACT ID", "NAME", "EMAIL", "INITIAL DONATION", "YEAR SUM");
      $contents = array();

      $query_string = "SELECT cc.contact_id, MIN(cc.receive_date) AS receive_date, cc.total_amount AS sum, SUM(cc.total_amount) AS year_sum, civicrm_contact.display_name, civicrm_email.email 
                      FROM civicrm_contribution cc 
                      LEFT JOIN civicrm_contact ON cc.contact_id = civicrm_contact.id
                      LEFT JOIN civicrm_email ON cc.contact_id = civicrm_email.contact_id AND civicrm_email.is_primary = '1'";
      if($params['group']){
        $group_filter = (int)$params['group'];
        $query_string .= " LEFT JOIN civicrm_group_contact on cc.contact_id = civicrm_group_contact.contact_id
                          WHERE civicrm_group_contact.group_id = $group_filter";
      }  
      $query_string .= " GROUP BY contact_id HAVING receive_date >= %1 and receive_date < %2";
      
      //set up and format dates
      $year = $params['target_year'];
      $start = new DateTime($year.'-01-01');
      $end = new DateTime($year.'-01-01');
      $end->add(new DateInterval('P1Y'));
      $start = $start->format('Y-m-d H:i:s');
      $end = $end->format('Y-m-d H:i:s');

      //run previously-prepped query string with appropriate dates
      $dates = array(1 => array($start, 'String'), 2 => array($end, 'String'));
      $result = CRM_Core_DAO::executeQuery($query_string, $dates);

      while($result->fetch()){
        $content = array($result->contact_id, $result->display_name, $result->email, $result->sum, $result->year_sum);
        $contents[] = $content;
      }

    }//end new

    //lapsed/upgraded/downgraded/maintained
    else{
      $report_type = $params['report_type'];
      $titles = array("CONTACT ID", "NAME", "EMAIL", "PREV YEAR SUM", "YEAR SUM");
      $contents = array();
  
      $query_string = "SELECT cc.contact_id, SUM(total_amount) as total FROM civicrm_contribution cc";
      $query_string_where = " WHERE cc.receive_date >= %1 and cc.receive_date < %2";
      
      if($params['group']){
        $group_filter = (int)$params['group'];
        $query_string .= " LEFT JOIN civicrm_group_contact ON cc.contact_id = civicrm_group_contact.contact_id";
        $query_string_where .= " AND civicrm_group_contact.group_id = $group_filter";
      }
      $query_string .= $query_string_where;
      $query_string .= " GROUP BY contact_id";  
  
      //set up and format dates
      $year = $params['target_year'];
      $start = new DateTime($year.'-01-01');
      $end = new DateTime($year.'-01-01');
      $end->add(new DateInterval('P1Y'));
      $start = $start->format('Y-m-d H:i:s');
      $end = $end->format('Y-m-d H:i:s');
      $start_prev = new DateTime($year.'-01-01');
      $end_prev = new DateTime($year.'-01-01');
      $start_prev->sub(new DateInterval('P1Y'));
      $start_prev = $start_prev->format('Y-m-d H:i:s');
      $end_prev = $end_prev->format('Y-m-d H:i:s');
      
      //get donors and total amount donated current year and year before
      $dates = array(1 => array($start, 'String'), 2 => array($end, 'String'));
      $result = CRM_Core_DAO::executeQuery($query_string, $dates);
      $dates_prev = array(1 => array($start_prev, 'String'), 2 => array($end_prev, 'String'));
      $result_prev = CRM_Core_DAO::executeQuery($query_string, $dates_prev);

      $result_array = array();
      $result_prev_array = array();
      $year_sum = array();
      $year_prev_sum = array();
      $contact_ids = array();
      //set up two arrays for comparing
      while($result->fetch()){
        $result_array[$result->contact_id] = $result->total;
      }
      while($result_prev->fetch()){
        $result_prev_array[$result_prev->contact_id] = $result_prev->total;
      }
      if($report_type == '2'){ //lapsed
        foreach($result_prev_array as $contact_id => $total){
          if(!array_key_exists($contact_id,$result_array)){
            $year_sum[$contact_id] = '0'; //target year will naturally be 0 for lapsed donors
            $year_prev_sum[$contact_id] = $total; //save total for display later
            $contact_ids[] = $contact_id;  //save contact ids for querying name and email
          }
        }
      }
      elseif($report_type == '3'){ //upgraded
        foreach($result_array as $contact_id => $total){
          if(array_key_exists($contact_id,$result_prev_array) && $total > $result_prev_array[$contact_id]){
            $year_sum[$contact_id] = $total; //save total for display later
            $year_prev_sum[$contact_id] = $result_prev_array[$contact_id];
            $contact_ids[] = $contact_id;  //save contact ids for querying name and email
          }
        }
      }  
      elseif($report_type == '4'){ //downgraded
        foreach($result_array as $contact_id => $total){
          if(array_key_exists($contact_id,$result_prev_array) && $total < $result_prev_array[$contact_id]){
            $year_sum[$contact_id] = $total; //save total for display later
            $year_prev_sum[$contact_id] = $result_prev_array[$contact_id];
            $contact_ids[] = $contact_id;  //save contact ids for querying name and email
          }
        }
      }
      elseif($report_type == '5'){ //maintained
        foreach($result_array as $contact_id => $total){
          if(array_key_exists($contact_id,$result_prev_array) && $total == $result_prev_array[$contact_id]){
            $year_sum[$contact_id] = $total; //save total for display later
            $year_prev_sum[$contact_id] = $result_prev_array[$contact_id];
            $contact_ids[] = $contact_id;  //save contact ids for querying name and email
          }
        }
      }
          
      if($contact_ids){ //we have contact; grab name and email
        $contact_ids_string = implode(",", $contact_ids); 
        $query_string ="SELECT civicrm_contact.id, civicrm_contact.display_name, civicrm_email.email 
                        FROM civicrm_contact
                        LEFT JOIN civicrm_email ON civicrm_contact.id = civicrm_email.contact_id
                        WHERE (civicrm_email.is_primary = '1' OR civicrm_email.is_primary IS NULL) 
                        AND civicrm_contact.id IN ($contact_ids_string)";
        
        //$contact_ids = array(1 => array($contact_ids_string, 'String'));
        $result = CRM_Core_DAO::executeQuery($query_string);
        while($result->fetch()){                                                        
          $content = array($result->id, $result->display_name, $result->email, $year_prev_sum[$result->id], $year_sum[$result->id]);    
          $contents[] = $content;
        }  
      }

      switch($report_type){
        case 2: 
          $report_name = 'lapsed';
          break;
        case 3: 
          $report_name = 'upgraded';
          break;
        case 4: 
          $report_name = 'downgraded';
          break;
        case 5: 
          $report_name = 'maintained';
          break;
      }          

    }//end lapsed/upgraded/downgraded/maintained
    //crm_core_error::debug('test', $contents);
    if (isset($params['_qf_Trends_submit'])) {
      if($report_name == 'overview') $year = $curr_year; 
      CRM_Core_Report_Excel::writeCSVFile($year.'_'.$report_name, $titles, $contents);
      CRM_Utils_System::civiExit();
    }
    else{
      //assign rows to template and reload the page
      $this->assign('report_name', $report_name);
      if($year){
        $this->assign('target_year', $year);
      }
      if(isset($group_filter)){
        $group_filters = CRM_Core_PseudoConstant::allGroup();
        $this->assign('group_filter', $group_filters[$group_filter]);
      }  
      $this->assign('title', $titles);
      $this->assign('content', $contents);
    }
 
  }//end postProcess function
}  
