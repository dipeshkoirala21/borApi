<?php

	/**
	 * 
	 */
	class DbOperations{
		
		private $con;

		//constructor of the DbOperations class
		function __construct()
		{
			require_once dirname(__FILE__) . '/DbConnect.php'; // importing the DbConnect class from the above directory
			
			$db = new DbConnect;// creating object of DbConnect class

			$this->con = $db->connect();//storing the DbConnect in the con variable
		}

		// this function stores the user in the database
		public function createUser($name, $email, $password, $contact,  $date_of_birth){
			if (!$this->isEmailExist($email)) {
				$statement = $this->con->prepare("INSERT INTO users (name, email, password, contact, date_of_birth) VALUES (?, ?, ?, ?, ?)");
				$statement->bind_param("sssss", $name, $email, $password, $contact, $date_of_birth);// binding the parameter to insert in the above query
				//executing the query
				if($statement->execute()){
					return USER_CREATED;

				}else{
					return USER_FAILURE;
				}
			}
			return USER_EXISTS;

		}

		// this function will authenticate the user
		public function userLogin($email, $password){
            if($this->isEmailExist($email)){
                $hashed_password = $this->getUsersPasswordByEmail($email); 
                if(password_verify($password, $hashed_password)){
                    return USER_AUTHENTICATED;
                }else{
                    return USER_PASSWORD_DO_NOT_MATCH; 
                }
            }else{
                return USER_NOT_FOUND; 
            }
        }

        private function getUsersPasswordByEmail($email){
            $statement = $this->con->prepare("SELECT password FROM users WHERE email = ?");
            $statement->bind_param("s", $email);
            $statement->execute(); 
            $statement->bind_result($password);
            $statement->fetch(); 
            return $password; 
        }

        public function getAllUsers(){
            $statement = $this->con->prepare("SELECT id, name, email, contact, date_of_birth FROM users;");
            $statement->execute(); 
            $statement->bind_result($id, $name, $email, $contact, $date_of_birth);
            $users = array(); //users arra
            while($statement->fetch()){ 
                $user = array(); 
                $user['id'] = $id; 
                $user['name']=$name; 
                $user['email'] = $email; 
                $user['contact'] = $contact;
                $user['date_of_birth'] = $date_of_birth; 
                array_push($users, $user);//pushing user inside users array
            }             
            return $users; 
        }

        // this funtion returns the user
        public function getUserByEmail($email){
            $statement = $this->con->prepare("SELECT id, name, email, contact, date_of_birth FROM users WHERE email = ?");
            $statement->bind_param("s", $email);
            $statement->execute(); 
            $statement->bind_result($id, $name, $email, $contact, $date_of_birth);
            $statement->fetch(); 
            $user = array(); 
            $user['id'] = $id; 
            $user['name']=$name; 
            $user['email'] = $email;  
            $user['contact'] = $contact;
            $user['date_of_birth'] = $date_of_birth; 
            return $user; 
        }

        public function updateUser($id, $name, $email, $contact, $date_of_birth){
            $statement = $this->con->prepare("UPDATE users SET name = ?, email = ?, contact = ?, date_of_birth = ? WHERE id = ?");
            $statement->bind_param("ssssi", $name, $email, $contact, $date_of_birth, $id );
            if($statement->execute())
                return true; 
            return false; 
        }

        public function updatePassword($currentpassword, $newpassword, $email){
            $hashed_password = $this->getUsersPasswordByEmail($email);
            
            if(password_verify($currentpassword, $hashed_password)){
                
                $hash_password = password_hash($newpassword, PASSWORD_DEFAULT);
                $statement = $this->con->prepare("UPDATE users SET password = ? WHERE email = ?");
                $statement->bind_param("ss",$hash_password, $email);

                if($statement->execute())
                    return PASSWORD_CHANGED;
                return PASSWORD_NOT_CHANGED;

            }else{
                return PASSWORD_DO_NOT_MATCH; 
            }
        }

        public function deleteUser($id){
            $statement = $this->con->prepare("DELETE FROM users WHERE id = ?");
            $statement->bind_param("i", $id);
            if($statement->execute())
                return true; 
            return false; 
        }

			//this function checks whether email already exists or not
			private function isEmailExist($email){
				$statement = $this->con->prepare("SELECT id FROM users WHERE email= ?");
				$statement->bind_param("s", $email);
				$statement->execute();
				$statement->store_result();
				return $statement->num_rows > 0;
			}
	}