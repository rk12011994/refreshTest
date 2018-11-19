<?php

namespace App\Helpers;
use Illuminate\Support\Facades\DB;
use Request;
use App\State, App\City, App\Company, App\Branch, App\Blog, App\User, App\SigningRequest;
use Mail ;
use App\OrderLog;
use Carbon;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\Customer;
 
class Helper
{

    /**QuickBooks Section*/
    public static function DataServiceObject() {
        
        $dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => env('CLIENT_ID'),
            'ClientSecret' => env('CLIENT_SECRET'),
            'accessTokenKey' => env('ACCESS_TOKEN'),
            'refreshTokenKey' => env('REFRESH_TOKEN'),
            'QBORealmID' => env('COMPANY_ID'),
            'baseUrl' => "Development"
        ));

        return $dataService;
    }

    public static function createCustomer($id) {

        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

        $customer = Contact::with(['user', 'branch.company', 'branch.State', 'branch.City'])->where('contact_id', $id)->first();

        if(!isset($customer->branch->qbListID)) {
            Helper::createBranch($customer->branch->id);
        }

        if (strlen($customer->user->last_name.', '.$customer->user->first_name.' ('.$customer->user->id.')') > 40)
        {
            $len = 40-strlen(' ('.$customer->user->id.')');
            $name_string = substr($customer->user->last_name.', '.$customer->user->first_name,0,$len).' ('.$customer->user->id.')';

        }
        else
        {
            $name_string = $customer->user->last_name.', '.$customer->user->first_name.' ('.$customer->user->id.')';
        }

        if(strlen($customer->branch->company->company) > 40)
        {
            $company['name'] = substr($customer->branch->company->company,0,40);
        } else {
            $company['name'] = $customer->branch->company->company;
        }

        $theResourceObj = Customer::create([
            "DisplayName" => $name_string,
            "GivenName" => $customer->user->first_name,
            "FamilyName" => $customer->user->last_name,
            "CompanyName" => $company['name'],
            "BillAddr" => [
                "Line1" => $company['name'],
                "Line2" => $customer->branch->address,
                "City" => $customer->branch->City->city,
                "Country" => "U.S.A",
                "CountrySubDivisionCode" => $customer->branch->State->code,
                "PostalCode" => $customer->branch->zip
            ],
            "PrimaryPhone" => [
                "FreeFormNumber" => $customer->branch->business_no
            ],
            "Mobile" => [
                "FreeFormNumber" => isset($customer->mobile_phone) ? $customer->mobile_phone : ''
            ],
            "Fax" => [
                "FreeFormNumber" => isset($customer->branch->fax_no) ? $customer->branch->fax_no : ''
            ],
            "PrimaryEmailAddr" => [
                "Address" => $customer->user->email
            ],
            "PrintOnCheckName" => $customer->user->first_name.' '.$customer->user->last_name,
            "CustomerRef" => [
                "value" => isset($customer->branch->qbListID) ? $customer->branch->qbListID : ''
            ],
        ]);

        $resultingObj = $dataService->Add($theResourceObj);
        $error = $dataService->getLastError();
        if ($error) {
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
        }
        else {
            $input['qbListID'] = $resultingObj->Id;
            $user = User::find($customer->user->id);
            $user->update($input);
            return 1;
        }

    }

    public static function updateCustomer($qbListID) {
        
        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

        $qbCustomer = $dataService->FindbyId('customer', $qbListID);

        $customer = Contact::with(['user', 'company', 'branch.State', 'branch.City'])->where('qbListID', $qbListID)->first();

        if (strlen($customer->user->last_name.', '.$customer->user->first_name.' ('.$customer->user->id.')') > 40)
        {
            $len = 40-strlen(' ('.$customer->user->id.')');
            $name_string = substr($customer->user->last_name.', '.$customer->user->first_name,0,$len).' ('.$customer->user->id.')';

        }
        else
        {
            $name_string = $customer->user->last_name.', '.$customer->user->first_name.' ('.$customer->user->id.')';
        }

        if(strlen($customer->company->company) > 40)
        {
            $company['name'] = substr($customer->company->company,0,40);
        } else {
            $company['name'] = $customer->company->company;
        }

        $theResourceObj = Customer::update($qbCustomer  , [
            "DisplayName" => $name_string,
            "GivenName" => $customer->user->first_name,
            "FamilyName" => $customer->user->last_name,
            "CompanyName" => $company['name'],
            "BillAddr" => [
                "Line1" => $company['name'],
                "Line2" => $customer->branch->address,
                "City" => $customer->branch->City->city,
                "Country" => "U.S.A",
                "CountrySubDivisionCode" => $customer->branch->State->code,
                "PostalCode" => $customer->branch->zip
            ],
            "PrimaryPhone" => [
                "FreeFormNumber" => $customer->branch->business_no
            ],
            "Mobile" => [
                "FreeFormNumber" => isset($customer->mobile_phone) ? $customer->mobile_phone : ''
            ],
            "Fax" => [
                "FreeFormNumber" => isset($customer->branch->fax_no) ? $customer->branch->fax_no : ''
            ],
            "PrimaryEmailAddr" => [
                "Address" => $customer->user->email
            ],
            "PrintOnCheckName" => $customer->user->first_name.' '.$customer->user->last_name,
            "CustomerRef" => [
                "value" => isset($customer->branch->qbListID) ? $customer->branch->qbListID : ''
            ],
        ]);

        $resultingObj = $dataService->Update($theResourceObj);
        $error = $dataService->getLastError();
        if ($error) {
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
        }
        else {
            return 1;
        }
    }

    public function createCompany($id) {
        
        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

        $company = Company::where('id', $id)->first();

        if (strlen($company->company.' ('.$company->id.')') > 40)
        {
            $len = 40-strlen(' ('.$company->id.')');
            $name_string = substr($company->company,0,$len).' ('.$company->id.')';

        }
        else
        {
            $name_string = $company->company.' ('.$company->id.')';
        }

        if(strlen($company->company) > 40)
        {
            $nameCompany = substr($company->company,0,40);
        } else {
            $nameCompany = $company->company;
        }

        $theResourceObj = Customer::create([
            "DisplayName" => $name_string,
            "CompanyName" => $nameCompany,
            "WebAddr" => [
                "URI" => isset($company->website) ? $company->website : ''
            ]
        ]);

        $resultingObj = $dataService->Add($theResourceObj);
        $error = $dataService->getLastError();
        if ($error) {
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
        }
        else {
            $input['qbListID'] = $resultingObj->Id;
            $company->update($input);
            return 1;
        }
    }

    public function updateCompany($qbListID) {
        
        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

        $qbCustomer = $dataService->FindbyId('customer', $qbListID);

        $company = Company::where('qbListID', $qbListID)->first();

        if (strlen($company->company.' ('.$company->id.')') > 40)
        {
            $len = 40-strlen(' ('.$company->id.')');
            $name_string = substr($company->company,0,$len).' ('.$company->id.')';

        }
        else
        {
            $name_string = $company->company.' ('.$company->id.')';
        }

        if(strlen($company->company) > 40)
        {
            $nameCompany = substr($company->company,0,40);
        } else {
            $nameCompany = $company->company;
        }

        $theResourceObj = Customer::update($qbCustomer  , [
            "DisplayName" => $name_string,
            "CompanyName" => $nameCompany,
            "WebAddr" => [
                "URI" => isset($company->website) ? $company->website : ''
            ]
        ]);

        $resultingObj = $dataService->Update($theResourceObj);
        $error = $dataService->getLastError();
        if ($error) {
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
        }
        else {
            return 1;
        }
    }

    public static function createBranch($id) {

        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

        $branch = Branch::with(['company', 'State', 'City'])->where('id', $id)->first();

        if(!isset($branch->company->qbListID)) {
            Helper::createCompany($branch->company->id);
        }

        if (strlen($branch->City->city.', '.$branch->State->name.' ('.$branch->id.')') > 40)
        {
            $len = 40-strlen(' ('.$branch->id.')');
            $name_string = substr($branch->City->city.', '.$branch->State->name,0,$len).' ('.$branch->id.')';
        }
        else
        {
            $name_string = $branch->City->city.', '.$branch->State->name.' ('.$branch->id.')';
        }
        if(strlen($company['name']) > 40)
        {
            $company['name'] = substr($branch->company->company,0,40);
        } else {
            $company['name'] = $branch->company->company;
        }

        $theResourceObj = Customer::create([
            "DisplayName" => $name_string,
            "CompanyName" => $company['name'],
            "BillAddr" => [
                "Line1" => $company['name'],
                "Line2" => $branch->address,
                "City" => $branch->City->city,
                "Country" => "U.S.A",
                "CountrySubDivisionCode" => $branch->State->code,
                "PostalCode" => $branch->zip
            ],
            "PrimaryPhone" => [
                "FreeFormNumber" => $branch->business_no
            ],
            "Fax" => [
                "FreeFormNumber" => isset($branch->fax_no) ? $branch->fax_no : ''
            ],
            "PrintOnCheckName" => $company['name'],
            "CustomerRef" => [
                "value" => $branch->company->qbListID
            ],
        ]);

        $resultingObj = $dataService->Add($theResourceObj);
        $error = $dataService->getLastError();
        if ($error) {
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
        }
        else {
            $input['qbListID'] = $resultingObj->Id;
            $branch->update($input);
            return 1;
        }

    }

    public static function updateBranch($qbListID) {

        $dataService = Helper::DataServiceObject();
        
        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

        $qbCustomer = $dataService->FindbyId('customer', $qbListID);

        $branch = Branch::with(['company', 'State', 'City'])->where('qbListID', $qbListID)->first();

        if (strlen($branch->City->city.', '.$branch->State->name.' ('.$branch->id.')') > 40)
        {
            $len = 40-strlen(' ('.$branch->id.')');
            $name_string = substr($branch->City->city.', '.$branch->State->name,0,$len).' ('.$branch->id.')';
        }
        else
        {
            $name_string = $branch->City->city.', '.$branch->State->name.' ('.$branch->id.')';
        }
        if(strlen($company['name']) > 40)
        {
            $company['name'] = substr($branch->company->company,0,40);
        } else {
            $company['name'] = $branch->company->company;
        }

        $theResourceObj = Customer::create([
            "DisplayName" => $name_string,
            "CompanyName" => $company['name'],
            "BillAddr" => [
                "Line1" => $company['name'],
                "Line2" => $branch->address,
                "City" => $branch->City->city,
                "Country" => "U.S.A",
                "CountrySubDivisionCode" => $branch->State->code,
                "PostalCode" => $branch->zip
            ],
            "PrimaryPhone" => [
                "FreeFormNumber" => $branch->business_no
            ],
            "Fax" => [
                "FreeFormNumber" => isset($branch->fax_no) ? $branch->fax_no : ''
            ],
            "PrintOnCheckName" => $company['name'],
            "CustomerRef" => [
                "value" => $branch->company->qbListID
            ],
        ]);

        $resultingObj = $dataService->Update($theResourceObj);
        $error = $dataService->getLastError();
        if ($error) {
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
        }
        else {
            return 1;
        }

    }

    public static function createAgent($id) {

        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

    }

    public static function updateAgent($qbListID) {

        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

    }

    public static function createInvoice() {

        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

    }

    public static function updateInvoice() {

        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

    }

    public static function voidInvoice() {

        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

    }

    public static function createBill() {

        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

    }

    public static function updateBill() {

        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

    }

    public static function voidBill() {

        $dataService = Helper::DataServiceObject();

        $dataService->setLogLocation("/Users/PHP/Desktop/newFolderForLog");
        $dataService->throwExceptionOnError(true);

    }
    /**QuicBooks Section Ends Here*/

    /**function to get average time */
    public static function avgTime($seconds = 0){
        $day = $hours = $minutes =  0;
        
        if(strpos($seconds,'.')) {
            
            $temp = explode('.', $seconds);

            $seconds = $temp[0];
        }
        
        $minutes = ($seconds/60);

        $temp = explode('.', $minutes);

        $seconds = $seconds - ($temp[0]*60);

        $minutes = $temp[0];

        $hours = $minutes/60;

        $temp = explode('.', $hours);

        $minutes = $minutes - ($temp[0]*60); //final minutes

        $hours = $temp[0];

        $day = $hours/24; 

        $temp = explode('.', $day);

        $hours = $hours - ($temp[0]*24); //final hours

        $day = $temp[0]; //final days

        if($hours < 10) {
            $hours = '0'.$hours;
        }
        if($minutes < 10) {
            $minutes = '0'.$minutes;
        }
        if($seconds < 10) {
            $seconds = '0'.$seconds;
        }
        if($day == 0) {
            $avg_time = $hours.':'.$minutes.':'.$seconds;
        } else {
            $avg_time = $day.' days'.' '.$hours.':'.$minutes.':'.$seconds;
        }

        return $avg_time;
    }
    /** get count of scheduler order taken*/
    public static function schedulerOrder($id,$action) {
        $orders = new SigningRequest;
        if($action == 'taken') {
            $orders = SigningRequest::where('scheduler_id',$id)->get();
        }
        if($action == 'scheduled') {
            $orders = SigningRequest::where('scheduler_id',$id)->whereNotNull('agent_id')->get();
        }
        if($action == 'onHold') {
            $orders = SigningRequest::where('scheduler_id',$id)->where('onHold', 1)->get();
        }
        if($action == 'cancelled') {
            $orders = SigningRequest::where('scheduler_id',$id)->where('cancelled', 1)->get();
        }
        if($action == 'avg-time') {
            $orders = SigningRequest::where('scheduler_id',$id)->get();
            if($orders->count()) {
                $seconds = 0;
                foreach($orders as $order) {
                    $start = Carbon\Carbon::parse($order->created_at);
                    $end = Carbon\Carbon::parse($order->updated_at);
                    $second = $end->diffInSeconds($start);
                    $seconds = $seconds + $second;
                }
                $seconds = $seconds/$orders->count();
                /* $seconds = 7583;
                $dtF = new \DateTime('@0');
                $dtT = new \DateTime("@$seconds");
                return $dtF->diff($dtT)->format('%d days, %h:%i:%s'); */
                $avg_time = Helper::avgTime($seconds);

                return $avg_time;
            } else {
                return 'No Signing done yet';
            }
        }
        return $orders->count();
    }
    /** get order of scheduler where agent not assigned*/
    public static function schedulerPipeline($id) {
        $orders = SigningRequest::where('scheduler_id',$id)->where('agent_id', null)->paginate();
        return $orders;
    }
	/**get recent posts */
	public static function posts() {
		$posts =  Blog::orderBy('id', 'DESC')->take(5)->get();
		return $posts;
	}
   /***get all states from DB***/
    public static function states(){
        return State::all();
    }
    
    /***get all cities from DB***/
    public static function cities(){
        return City::all();
    }
    public static function citiesformail($id){
        return City::where('state_id',$id)->get();
    }
    /***send notification mail***/
    public static function registerMail($input){
         $status= Mail::send('emails.register',['input'=> $input], function($message)use ($input) {
                    $message->to($input['email'], $input['name'])
                        ->subject('Thank you for registraion');
                });
        return $status; 
    }
    /***get all companies from DB***/
    public static function companies(){
        return Company::all();
    }
    /***yearly calendars**/
    public static function draw_calendar($month,$year){

	/* draw table */
	//$calendar = '<table cellpadding="0" cellspacing="0" class="calendar">';
        $calendar ='';
	/* table headings */
	$headings = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
	//$calendar.= '<tr class="calendar-row"><td class="calendar-day-head">'.implode('</td><td class="calendar-day-head">',$headings).'</td></tr>';

	/* days and weeks vars now ... */
	$running_day = date('w',mktime(0,0,0,$month,1,$year));
	$days_in_month = date('t',mktime(0,0,0,$month,1,$year));
	$days_in_this_week = 1;
	$day_counter = 0;
	$dates_array = array();

	/* row for week one */
	$calendar.= '<tr class="calendar-row">';
        $calendar.='<td class="smtext" height="29">Date<br>Signed </td>';
	/* print "blank" days until the first of the current week */
	for($x = 0; $x < $running_day; $x++):
		//$calendar.= '<td class="calendar-day-np"> </td>';
		$days_in_this_week++;
	endfor;

	/* keep going with days.... */
	for($list_day = 1; $list_day <= $days_in_month; $list_day++):
		$calendar.= '<td class="calendar-day">';
			/* add in the day number */
			$calendar.= '<div class="day-number">'.$list_day.'</div>';

			/** QUERY THE DATABASE FOR AN ENTRY FOR THIS DAY !!  IF MATCHES FOUND, PRINT THEM !! **/
			$calendar.= str_repeat('<p> </p>',2);
			
		$calendar.= '</td>';
		if($running_day == 6):
			//$calendar.= '</tr>';
			if(($day_counter+1) != $days_in_month):
				//$calendar.= '<tr class="calendar-row">';
			endif;
			$running_day = -1;
			$days_in_this_week = 0;
		endif;
		$days_in_this_week++; $running_day++; $day_counter++;
	endfor;

	/* finish the rest of the days in the week */
	if($days_in_this_week < 8):
		for($x = 1; $x <= (8 - $days_in_this_week); $x++):
			//$calendar.= '<td class="calendar-day-np"> </td>';
		endfor;
	endif;

	/* final row */
	$calendar.= '</tr>';
        $calendar.= Helper::monthlyOrders($month,$year);
	/* end the table */
	//$calendar.= '</table>';
	
	/* all done, return result */
	return $calendar;
    }
    /*****get orders from month for a agent*****/
    public static function monthlyOrders($month,$year){
        $days_in_month = date('t',mktime(0,0,0,$month,1,$year));
        $left_days =1;
        /* row for week one */
	$calendar= '<tr class="calendar-row">';
        $calendar.='<td class="smtext" height="29">Recission<br>Expires </td>';
	/* print "blank" days until the first of the current week */
	 
	/* keep going with days.... */
	for($list_day = 1; $list_day <= $days_in_month; $list_day++):
		$calendar.= '<td class="calendar-day">';
			/* add in the day number */
                    if($left_days > 0){     
                        $date=$list_day.'-'.$month.'-'.$year;
                        if(Helper::isWeekend($date)){
                             $weekend = $list_day+4;
                              if($weekend==$days_in_month){
                                 $days_in_month = ($days_in_month-$list_day);
                               //  $days_in_month=$left_days;
                                 $list_day=0;
                                 $left_days =0;
                             }
                             $calendar.= '<div class="day-number">'.$weekend.'</div>';
                            
                        }else{
                             $weekdays = $list_day+3;
                             if($weekdays==$days_in_month){
                                 $days_in_month = ($days_in_month-$list_day);
                                // $days_in_month=$left_days;
                                 $list_day=0;
                                 $left_days =0;
                             }
                            $calendar.= '<div class="day-number">'.$weekdays.'</div>';
                            
                        } 
                    }else{
                         
                         $calendar.= '<div class="day-number">'.$list_day.'</div>';
                    }
			/** QUERY THE DATABASE FOR AN ENTRY FOR THIS DAY !!  IF MATCHES FOUND, PRINT THEM !! **/
			$calendar.= str_repeat('<p> </p>',2);
			
		$calendar.= '</td>';
		 
		 
	endfor;

	/* finish the rest of the days in the week */
	 
	/* final row */
	$calendar.= '</tr>';
        return $calendar;
    }
    public static function isWeekend($date) {
        $weekDay = date('w', strtotime($date));
        return ($weekDay == 4 || $weekDay == 5 || $weekDay == 6);
    }
    /***create order log***/
    public static function orderLog($orderId, $userId, $isPublic, $comments ,$status_changed_from, $status_changed_to){
            $log['user_id'] = $userId;
            $log['is_public']=$isPublic;
            $log['order_id']= $orderId;
            $log['comments']= $comments;
            $log['status_changed_from']= $status_changed_from;
            $log['status_changed_to']= $status_changed_to;
            return OrderLog::create($log);
    }
    /**send mail common function***/
    public static function sendmail($recipient,$subject,$message=false,$view,$data){
       Mail::send($view, [$data], function ($message) use($recipient,$subject){
          $message->to($recipient->email, $recipient->name)
                  ->subject($subject);    
        });
        return true;
    }
    
    /**
    * Return nav-here if current path begins with this path.
    *
    * @param string $path
    * @return string
    */
   public static function setActiveRelative($path)
   {
       $class = Request::is($path . '*') ? 'active' :  '';
       return $class;
   }
   /**match accurate path**/
   public static function setActiveAbsolute($path)
   {  
       $class = Request::is($path) ? 'active' :  '';
       return $class;
   }

}
