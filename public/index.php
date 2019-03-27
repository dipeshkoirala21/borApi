<?php

use Psr\Http\Message\ServerRequestInterface as Request;//getting serverrequestinterface as request
use Psr\Http\Message\ResponseInterface as Response;//getting response interface as response

require '../vendor/autoload.php';

require '../includes/DbOperations.php';

$app = new \Slim\App([
	'settings'=>[
		'displayErrorDetails'=>true
	]
]);//creating new slim app

/*
	endpoint: createuser
	parameters: name,email, password, contact, date_of_birth
	method: POST
*/

$app->post('/createuser', function(Request $request, Response $response){

	if (!haveEmptyParameters(array('name', 'email', 'password','contact','date_of_birth'), $request, $response)) {
		
		$request_data = $request->getParsedBody();
		
		$name = $request_data['name'];
		$email = $request_data['email'];
		$password = $request_data['password'];
		$contact = $request_data['contact'];
		$date_of_birth = $request_data['date_of_birth'];

		$hash_password = password_hash($password, PASSWORD_DEFAULT);// encrypting password
		// creating object of DbOperations
		$db = new DbOperations;

		$result = $db->createUser($name, $email, $hash_password, $contact, $date_of_birth);

		if($result == USER_CREATED){

				$message = array();
				$message['error'] = false;
				$message['message'] = 'User Created Sucessfully';

				$response->write(json_encode($message));

				return $response
						->withHeader('Content-type', 'application/json')
						->withStatus(201);//201 means the request is complete and a new resource is created

		}else if($result == USER_FAILURE) {
				$message = array();
				$message['error'] = true;
				$message['message'] = 'Some Error Occurred';

				$response->write(json_encode($message));

				return $response
						->withHeader('Content-type', 'application/json')
						->withStatus(422);//422 means error occurred

		}else if($result == USER_EXISTS) {
			
				$message = array();
				$message['error'] = true;
				$message['message'] = 'User Already Exists';

				$response->write(json_encode($message));

				return $response
						->withHeader('Content-type', 'application/json')
						->withStatus(422);//422 means error occurred/ already exists
		}
	}
	return $response
		->withHeader('Content-type', 'application/json')
		->withStatus(422);//422 means error occurred/ already exists
});


$app->post('/userlogin', function(Request $request, Response $response){

    if(!haveEmptyParameters(array('email', 'password'), $request, $response)){
        $request_data = $request->getParsedBody(); 

        $email = $request_data['email'];
        $password = $request_data['password'];
        
        $db = new DbOperations; 

        $result = $db->userLogin($email, $password);

        if($result == USER_AUTHENTICATED){
            
            $user = $db->getUserByEmail($email);
            $response_data = array();

            $response_data['error']=false; 
            $response_data['message'] = 'Login Successful';
            $response_data['user']=$user; 

            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);    

        }else if($result == USER_NOT_FOUND){
            $response_data = array();

            $response_data['error']=true; 
            $response_data['message'] = 'User not exist';

            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);    

        }else if($result == USER_PASSWORD_DO_NOT_MATCH){
            $response_data = array();

            $response_data['error']=true; 
            $response_data['message'] = 'Invalid credential';

            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);  
        }
    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);    
});


$app->get('/allusers', function(Request $request, Response $response){

    $db = new DbOperations; 

    $users = $db->getAllUsers();

    $response_data = array();

    $response_data['error'] = false; 
    $response_data['users'] = $users; 

    $response->write(json_encode($response_data));

    return $response
    ->withHeader('Content-type', 'application/json')
    ->withStatus(200);  

});

$app->put('/updateuser/{id}', function(Request $request, Response $response, array $args){

    $id = $args['id'];

    if(!haveEmptyParameters(array('id', 'name','email','contact', 'date_of_birth'), $request, $response)){

        $request_data = $request->getParsedBody(); 
        $id = $request_data['id'];
        $name = $request_data['name'];
        $email = $request_data['email'];
        $contact = $request_data['contact'];
        $date_of_birth = $request_data['date_of_birth']; 
     

        $db = new DbOperations; 

        if($db->updateUser($id, $name, $email, $contact, $date_of_birth)){
            $response_data = array(); 
            $response_data['error'] = false; 
            $response_data['message'] = 'User Updated Successfully';
            $user = $db->getUserByEmail($email);
            $response_data['user'] = $user; 

            $response->write(json_encode($response_data));

            return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);  
        
        }else{
            $response_data = array(); 
            $response_data['error'] = true; 
            $response_data['message'] = 'Please try again later';
            $user = $db->getUserByEmail($email);
            $response_data['user'] = $user; 

            $response->write(json_encode($response_data));

            return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);  
              
        }

    }
    
    return $response
    ->withHeader('Content-type', 'application/json')
    ->withStatus(200);  

});
$app->put('/updatepassword', function(Request $request, Response $response){

    if(!haveEmptyParameters(array('currentpassword', 'newpassword', 'email'), $request, $response)){
        
        $request_data = $request->getParsedBody(); 

        $currentpassword = $request_data['currentpassword'];
        $newpassword = $request_data['newpassword'];
        $email = $request_data['email']; 

        $db = new DbOperations; 

        $result = $db->updatePassword($currentpassword, $newpassword, $email);

        if($result == PASSWORD_CHANGED){
            $response_data = array(); 
            $response_data['error'] = false;
            $response_data['message'] = 'Password Changed';
            $response->write(json_encode($response_data));
            return $response->withHeader('Content-type', 'application/json')
                            ->withStatus(200);

        }else if($result == PASSWORD_DO_NOT_MATCH){
            $response_data = array(); 
            $response_data['error'] = true;
            $response_data['message'] = 'You have given wrong password';
            $response->write(json_encode($response_data));
            return $response->withHeader('Content-type', 'application/json')
                            ->withStatus(200);
        }else if($result == PASSWORD_NOT_CHANGED){
            $response_data = array(); 
            $response_data['error'] = true;
            $response_data['message'] = 'Some error occurred';
            $response->write(json_encode($response_data));
            return $response->withHeader('Content-type', 'application/json')
                            ->withStatus(200);
        }
    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);  
});

$app->delete('/deleteuser/{id}', function(Request $request, Response $response, array $args){
    $id = $args['id'];

    $db = new DbOperations; 

    $response_data = array();

    if($db->deleteUser($id)){
        $response_data['error'] = false; 
        $response_data['message'] = 'User has been deleted';    
    }else{
        $response_data['error'] = true; 
        $response_data['message'] = 'Plase try again later';
    }

    $response->write(json_encode($response_data));

    return $response
    ->withHeader('Content-type', 'application/json')
    ->withStatus(200);
});


//verifying that all the required parameters are available
function haveEmptyParameters($required_params, $request, $response){
	$error = false; // this means we have all the parameters and no parameters are empty
	$error_params = '';// to check empty parameters
	$request_params = $request->getParsedBody();

	//looping thru the all the parameters above
	//we have all the parameters in the param
	foreach ($required_params as $param) {
		if(!isset($request_params[$param]) || strlen($request_params[$param])<=0){
			$error=true;
			$error_params .= $param . ', ';
		}
	}
    
	if ($error) {
		$error_detail = array();
		$error_detail['error'] = true;
		$error_detail['message'] = 'Required Parameters ' . substr($error_params, 0, -2) . ' are missing or empty';
		$response->write(json_encode($error_detail));
	}
	return $error;
}
$app->run(); 