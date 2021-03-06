<?php
######################################
# Import EURES jobs from scraped data into database
######################################

ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');

include("config.inc.php");
include("functions.php");
require 'scraperwiki/scraperwiki.php';
require 'scraperwiki/simple_html_dom.php';
include 'geocoderParser/GGeocoderParserLib.v1.php';

date_default_timezone_set("Europe/Dublin");

setup_database(true);

$country_dirs = array ('BG','CY','CZ','CH','DK','DE','EE','FI','FR','GR','HU','IR','IS','IT','LI','LT','LU','LV','MT','NL','NO','PL','PT','RO','SK','SI','ES','SE','UK');

//$debug_countries = array("DE");
//$debug_files = array("10000-1069568588","10000-1069592591");

echo PHP_EOL."= IMPORT =".PHP_EOL.PHP_EOL;

if (sizeof($debug_countries) > 0) {
  $country_dirs = $debug_countries;
}

foreach ($country_dirs as $country_dir) {
  $dir = $JOBS_DIR.$country_dir;
	if (is_dir($dir)) {
		if ($handle = opendir($dir)) {
      echo "Directory: ".$dir.PHP_EOL;
			while (false !== ($file = readdir($handle))) 
			{
				if (!is_dir($file)) {
          if ((sizeof($debug_files) > 0) && (!in_array($file, $debug_files))) {
            continue;
          }
          $title=NULL;
          $required_languages=NULL;
          $starting_date=NULL;
          $ending_date=NULL;
          $country=NULL;
          $region=NULL;
          $minimum_salary=NULL;
          $maximum_salary=NULL;
          $salary_comment = NULL;
          $salary_currency=NULL;
          $salary_tax=NULL;
          $salary_period=NULL;
          $hours_per_week=NULL;
          $contract=NULL;
          $contract_type=NULL;
          $contract_hours=NULL;
          $accommodation_provided=NULL;
          $relocation_covered=NULL;
          $meals_included=NULL;          $travel_expenses=NULL;
          $education_skills_required=NULL;
          $professional_qualifications_required=NULL;
          $experience_required=NULL;
          $driving_license_required=NULL;
          $minimum_age=NULL;
          $maximum_age=NULL;
          $name=NULL;
          $information=NULL;
          $address=NULL;
          $phone=NULL;
          $email=NULL;
          $fax=NULL;
          $how_to_apply=NULL;
          $contact=NULL;
          $last_date_for_application=NULL;
          $date_published=NULL;
          $national_reference=NULL;
          $last_modification_date=NULL;
          $nace_code=NULL;
          $isco_code=NULL;
          $isco_unit_code=NULL;
          $isco_minor_code=NULL;
          $isco_submajor_code=NULL;
          $isco_major_code=NULL;
          $number_of_posts=NULL;
          $other_value=NULL;
          $eures_reference=NULL;
          $contract_type_id=NULL;
          $contract_hours_id=NULL;
          $education_skills_id=NULL;
          $experience_id=NULL;
          $driving_license_id=NULL;
          $contact_id=NULL;
          $employer_id=NULL;
          $job_id=NULL;
          $address_array=NULL;
          $how_to_apply_id=NULL;
          $title_id=NULL;
          $homepage=NULL;
          $dom=NULL;
          $text=NULL;
          $data=NULL;
          $sql=NULL;
          $query=NULL;
          $row=NULL;
          $salary_currency_id=NULL;
          $salary_period_id=NULL;
          $salary_tax_id=NULL;
          $maximum_salary_array=NULL;
          $minimum_salary_array=NULL;
          $url=NULL;
          $country_code=NULL;
          $description=NULL;
          $source=NULL;
          $source_id=NULL;
          $url_id=NULL;
          $url_search=NULL;
          $url_scraper_date=NULL;
          $url_scraper_hour=NULL;
          $url_job_unique=NULL;
          $hours_per_week_array=NULL;
          $region_id=NULL;
          $fax_array=NULL;
          $phone_array = NULL;
          $address_id = NULL;
          $salary_period_id = NULL;

					$html = scraperwiki::scrape($dir."/".$file);
          
          if (!isset($html) or (strlen($html) == 0) or (!strpos($html, "EURES"))) {
            write_log('erroneous_files.log', array($country_dir,$file));
          } else {
            echo "Importing $file".PHP_EOL;
            
            $dom = new simple_html_dom();
            $dom->load($html);

            foreach($dom->find('div[@id=url_job]') as $data)
            {
              $url = $data->plaintext;
            }
            foreach($dom->find('div[@id=country_id]') as $data)
            {
              $country_code = $data->plaintext;
            }
            foreach($dom->find('div[@id=description]') as $data)
            {
              $description = $data->plaintext;
            }
            foreach($dom->find('div[@id=source]') as $data)
            {
              $source = $data->plaintext;
              $query = "SELECT id FROM source WHERE name = ".db_prep($source);
              $source_id = select_id($query);
              if (!$source_id) {
                mysql_query("INSERT INTO source SET name = ".db_prep($source)) or write_log("sources_errors.log", array($country_dir, $file, $source, mysql_error()));
                $source_id = select_id($query);
                write_log("new_sources.log", array($country_dir, $source_id, $source));
              }
            }
            foreach($dom->find('div[@id=url_id]') as $data)
            {
              $unique_id = $data->plaintext;
              if (!$unique_id) {
                write_log("uniqueid_errors.log", array($country_dir, $file));
              }
            }
            foreach($dom->find('div[@id=url_search]') as $data)
            {
              $url_search = $data->plaintext;
            }
            foreach($dom->find('div[@id=scraper_date]') as $data)
            {
              $url_scraper_date = $data->plaintext;
            }
            foreach($dom->find('div[@id=scraper_hour]') as $data)
            {
              $url_scraper_hour = $data->plaintext;
            }
            foreach($dom->find('div[@id=url_job_unique]') as $data)
            {
              $url_job_unique = $data->plaintext;
            }

            foreach($dom->find('th') as $data)
            {
              $text = trim($data->plaintext);

              if ($text <> 'Description:') {
                $value = trim($data->next_sibling()->plaintext);
              }
              //FIXME. HACK by Lucas, to get the data from Maximum salary, since the TH for this data in EURES pages is not well-written, causing the extractor not to work.
              //e.g. <th colspan="1">Maximum salary:</td>
              if (preg_match('/Maximum salary:/', $text)){
                $maximum_salary = trim(preg_replace("/[Maximum salary:|<\/td>]/","",$text));
                $maximum_salary_array = format_salary($maximum_salary);
              }
              else
              {
                switch($text) {
                  //Summary
                  case 'Title:':
                    $title = $value;
                    $title_id = insert_name('title',$title);
                    break;
                  case 'Description:':
                    break;
                  case 'Required languages:':
                    if (strlen($value) > 0) {
                      $required_languages = $value;
                    }
                    // inserted after job insertion
                    break;
                  case 'Starting Date:':
                    $starting_date = $value;
                    break;
                  case 'Ending date:':
                    $ending_date = $value;
                    break;
                  //Geographical Information
                  case 'Country:':$country = $value;break;
                  case 'Region:':
                    $region = $value;
                    if(($region != '') && ($region != '0') && ($region != '.')) {
                      $query = "SELECT id FROM region WHERE name = ".db_prep($region)." AND country_code = ".db_prep($country_code);
                      $region_id = select_id($query);
                      if (!isset($region_id)) {
                        mysql_query("INSERT INTO region SET name = ".db_prep($region).", country_code = ".db_prep($country_code)) or write_log("region_errors.log", array($country_dir, $file, $region, mysql_error()));
                        $region_id = select_id($query);
                      }
                    }
                    break;
                  //Salary / Contract
                  case 'Minimum salary:':
                    if (strlen($value) > 0) {
                      $minimum_salary = $value;
                      $minimum_salary_array = format_salary($minimum_salary);
                      if (!isset($minimum_salary_array["amount"])) {
                        $salary_comment = $value;
                      }
                    }
                    break;
                  case 'Maximum salary:':
                    if (strlen($value) > 0) {
                      $maximum_salary = $value;
                      $maximum_salary_array = format_salary($maximum_salary);
                    }
                    break;
                  case 'Salary currency:':
                    if (strlen($value) > 0) {
                      $salary_currency = $value;
                      $salary_currency_id = insert_name('salary_currency',$salary_currency);
                    }
                    break;
                  case 'Salary tax:':
                    if (strlen($value) > 0) {
                      $salary_tax = $value;
                      $salary_tax_id = insert_name('salary_tax',$salary_tax);
                    }
                    break;
                  case 'Salary period:':
                    if (strlen($value) > 0) {
                      $salary_period = $value;
                      $salary_period_id = insert_name('salary_period',$salary_period);
                    }
                    break;
                  case 'Hours per week:':
                    if (strlen($value) > 0) {
                      $hours_per_week = $value;
                      $hours_per_week_array = format_hour($hours_per_week);
                    }
                    break;
                  case 'Contract type:':
                    if (strlen($value) > 0) {
                      $contract = $value;
                      $contract = str_replace(")","",$contract);

                      if (strstr($contract, '('))
                        $explode_contract = explode("(",$contract);
                      elseif (strstr($contract, ' - '))
                        $explode_contract = explode(" - ",$contract);
                      else
                        $explode_contract = explode("+",$contract);

                      $contract_type = trim($explode_contract[0]);

                      $contract_type_id = insert_name('contract_type',$contract_type);

                      if (sizeof($explode_contract) > 1){
                        $contract_hours = trim($explode_contract[1]);

                        $contract_hours_id = insert_name('contract_hours',$contract_hours);
                      }
                    }
                    break;
                  //Extras
                  case 'Accommodation provided:':$accommodation_provided = prepare_boolean($value);break;
                  case 'Relocation covered:':$relocation_covered = prepare_boolean($value);break;
                  case 'Meals included:':$meals_included = prepare_boolean($value);break;
                  case 'Travel expenses:':$travel_expenses = prepare_boolean($value);break;
                  //Requirements
                  case 'Education skills required:':
                    $education_skills_required = $value;
                    $education_skills_id = insert_name('education_skills',$education_skills_required);
                    break;
                  case 'Professional qualifications required:': $professional_qualifications_required = prepare_boolean($value);break;
                  case 'Experience required:':
                    $experience_required = $value;
                    $experience_id = insert_name('experience',$experience_required);
                    break;
                  case 'Driving license required:':
                    $driving_license_required = $value;
                    $driving_license_id = insert_name('driving_license',$driving_license_required);
                    break;
                  case 'Minimum age:': 
                    $minimum_age = $value;
                    break;
                  case 'Maximum age:': 
                    $maximum_age = $value;
                    break;
                  //Employer
                  case 'Name:':
                    $name = $value;
                    break;
                  case 'Information:':
                    $information = $value;
                    break;
                  case 'Address:':
                    $address = $value;
                    $address_id = insert_address($address);
                    break;
                  case 'Phone:':
                    $phone = $value;
                    break;
                  case 'Email:':
                    $email = $value;
                    break;
                  case 'Fax:':
                    $fax = $value;
                    $fax_array = format_fax($fax);
                    break;
                  //Application
                  case 'How to apply:':
                    if (strlen($value) > 0) {
                      $how_to_apply = $value;
                      $how_to_apply_id = insert_name('how_to_apply',$how_to_apply);
                    }
                    break;
                  case 'Contact:':
                    $contact = $value;
                    break;
                  case 'Last date for application:':
                    $last_date_for_application = $value;
                    break;
                  //Other Information
                  case 'Date published:':
                    $date_published = $value;
                    break;
                  case 'National reference:':
                    $national_reference = $value;
                    break;
                  case 'Eures reference:':
                    $eures_reference = $value;
                    break;
                  case 'Last Modification Date:':
                    $last_modification_date = $value;
                    break;
                  case 'Nace code:':
                    $nace_code = $value;
                    break;
                  case 'ISCO code:':
                    $isco_code = $value;
                    break;
                  case 'Number of posts:':$number_of_posts = $value;break;
                  //FIXME. HACK by Lucas, to get the data from Contact, since the TH for this data in EURES pages is not well-written, causing the extractor not to work. e.g.<th colspan="1>Contact:</th>
                  default:$contact = trim(str_replace("</td>","",$text));$contact = str_replace("'","\'",$contact);break;
                }
              }
            }

            if ($salary_currency == NULL)
            {
              if (isset($minimum_salary_array['currency']))
                $salary_currency = $minimum_salary_array['currency'];
              elseif (isset($maximum_salary_array['currency']))
                $salary_currency = $maximum_salary_array['currency'];
            }

            if ($salary_period == NULL)
            {
              if (isset($minimum_salary_array['period']))
                $salary_currency = $minimum_salary_array['period'];
              elseif (isset($maximum_salary_array['period']))
                $salary_currency = $maximum_salary_array['period'];
            }

            if ($name <> '' || ($address_id <> NULL))
            {

              $query = "SELECT id FROM employer WHERE name = ".db_prep($name)." AND address_id = ".db_prep($address_id);
              $employer_id = select_id($query);
              if (!$employer_id)
              {
                mysql_query("INSERT INTO employer SET
                  name = ".db_prep($name).",
                  homepage = ".db_prep($homepage).",
                  address_id = ".db_prep($address_id).",
                  scraper_date = SYSDATE(),
                  scraper_hour = SYSDATE()"
                );
                $employer_id = select_id("SELECT LAST_INSERT_ID() FROM employer");
              }
            }

            if ($email != '' || $information != '' || sizeof($fax_array) > 0)
            {
              $query = "SELECT id FROM contact WHERE (email = ".db_prep($email)." OR email = ".db_prep($fax_array['email']).") AND information = ".db_prep($information)." AND fax = ".db_prep($fax_array["fax"])." AND country_code = ".db_prep($country_code);
              $query = str_replace("email = NULL ", "email is NULL ", $query);
              $query = str_replace(" fax = NULL ", " fax is NULL ", $query);
              $query = str_replace(" information = NULL ", " information is NULL ", $query);
              $contact_id = select_id($query);
              if (!$contact_id) {
                if ((strlen($email) == 0) && (isset($fax_array["email"]))) {
                  $email = $fax_array["email"];
                }
                mysql_query("INSERT INTO contact SET
                    employer_id = ".db_prep($employer_id).",
                    information = ".db_prep($information).",
                    country_code =".db_prep($country_code).",
                    email=".db_prep($email).",
                    fax = ".db_prep($fax_array['fax']).",
                    url = '$url',
                    scraper_date = SYSDATE(),
                    scraper_hour = SYSDATE()"
                );
                $contact_id = select_id($query);
              }

              if ($contact_id)
              {
                if($phone <> '')
                {
                  format_phone($phone);
                }
              }
            }

            if(!mysql_query("INSERT INTO job SET
              url = ".db_prep($url).",
              description = ".db_prep($description).",
              employer_id = ".db_prep($employer_id).",
              contact_id = ".db_prep($contact_id).",
              unique_id = '$unique_id',
              source_id = '$source_id',
              url_search = ".db_prep($url_search).",
              url_scraper_date = ".db_prep($url_scraper_date).",
              url_scraper_hour = ".db_prep($url_scraper_hour).",
              title_id = ".db_prep($title_id).",
              starting_date = STR_TO_DATE('$starting_date', '%d/%m/%Y'),
              ending_date = STR_TO_DATE('$ending_date', '%d/%m/%Y'),
              country_code = ".db_prep($country_code).",
              region_id = ".db_prep($region_id).",
              minimum_salary = ".db_prep($minimum_salary_array['amount']).",
              maximum_salary = ".db_prep($maximum_salary_array['amount']).",
              salary_comment = ".db_prep($salary_comment).",
              salary_currency_id = ".db_prep($salary_currency_id).",
              salary_tax_id = ".db_prep($salary_tax_id).",
              salary_period_id = ".db_prep($salary_period_id).",
              hours_per_week_min = ".db_prep($hours_per_week_array['min']).",
              hours_per_week_max = ".db_prep($hours_per_week_array['max']).",
              contract_type_id = ".db_prep($contract_type_id).",
              contract_hours_id = ".db_prep($contract_hours_id).",
              accommodation_provided = ".db_prep($accommodation_provided).",
              relocation_covered = ".db_prep($relocation_covered).",
              meals_included = ".db_prep($meals_included).",
              travel_expenses = ".db_prep($travel_expenses).",
              education_skills_id = ".db_prep($education_skills_id).",
              professional_qualifications_required = ".db_prep($professional_qualifications_required).",
              experience_id = ".db_prep($experience_id).",
              driving_license_id = ".db_prep($driving_license_id).",
              minimum_age = ".db_prep($minimum_age).",
              maximum_age = ".db_prep($maximum_age).",
              how_to_apply_id = ".db_prep($how_to_apply_id).",
              last_date_for_application = STR_TO_DATE('$last_date_for_application', '%d/%m/%Y'),
              date_published = STR_TO_DATE('$date_published', '%d/%m/%Y'),
              last_modification_date = STR_TO_DATE('$last_modification_date', '%d/%m/%Y'),
              nace_code = ".db_prep($nace_code).",
              national_reference = ".db_prep($national_reference).",
              eures_reference = ".db_prep($eures_reference).",
              isco_code = ".db_prep($isco_code).",
              number_of_posts = ".db_prep($number_of_posts).",
              job_scraper_date = SYSDATE(),
              job_scraper_hour = SYSDATE()"
            )) { 
              write_log("not_imported_job.logs", array($unique_id, $source_id, mysql_error()));
            } else {
              $job_id = select_id("SELECT id FROM job where unique_id = '$unique_id' AND source_id = '$source_id'");
              if ($required_languages) {
                split_required_languages($required_languages);
              }
            }
          }
        }
      }
			closedir($handle);
    }
    else
    {
      die("Could not open directory: " . $dir);
    }
	}
  else
	{
		echo "Directory does not exist: " . $dir.PHP_EOL;
	}
	mysql_query("INSERT INTO update_service SET date = SYSDATE(), hour = SYSDATE(), type='job'");
}

?>
