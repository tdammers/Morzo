<?php
require_once "controllers/controller.php";

class User extends Controller
{
	public function Logged_in()
	{
		session_start();

		if(!isset($_SESSION['userid']))
		{
			include 'views/not_logged_in.php';
			return false;
		}
		return true;
	}

	public function Index()
	{
		if(!$this->Logged_in())
		{
			return;
		}

		$this->Load_model('Actor_model');
		$actors = $this->Actor_model->Get_actors($_SESSION['userid']);

		include 'views/user_view.php';
	}
	
	public function RegisterQueue($email)
	{
		$email = mb_strtolower($email);
		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			echo 'Please provide a valid email. <br />';
		}
		else
		{
			include 'models/User_model.php';
			$this->Load_model('User_model');
			$r = $this->User_model->RegisterQueue($email);
			if($r === false)
			{
				echo 'Failed to register \' ' . $email . '\': ' . $r . ' <br />';
			}
			else
			{
				if($r == 'Duplicate')
				{
					echo $email . ' has already been registered. <br />';
				}
				else
				{
					echo 'Registered \' ' . $email . '\'. Thank you. <br />';
				}
			}
		}
	}
	
	public function Register($username, $password)
	{
		$this->Load_model('User_model');
		$r = $this->User_model->Register($username, $password);
		return $r;
	}
	
	public function Login($username, $password)
	{
		$this->Load_model('User_model');
		$r = $this->User_model->Login($username, $password);
		if(is_numeric($r))
		{
			session_start();
			if(isset($_SESSION['userid']))
			{
				return 0;
			}
			$_SESSION['username'] = $username;
			$_SESSION['userid'] = $r;
			$_SESSION['admin'] = $this->User_model->User_has_access($_SESSION['userid'], 'Admin');
			echo 1;
		}
		else
		{
			echo 0;
		}
	}

	public function Start_openid_login()
	{
		if(!isset($_POST['openid'])) {
			echo false;
			return;
		}
		
		session_start();

		require_once "Auth/OpenID/Consumer.php";
		require_once "Auth/OpenID/FileStore.php";

		$store = new Auth_OpenID_FileStore('./oid_store');
		$consumer = new Auth_OpenID_Consumer($store);

		$auth = $consumer->begin($_POST['openid']);
		if (!$auth) {
			echo false;
			return;
		}

		$url = $auth->redirectURL('http://dev.trezker.net/', 'http://dev.trezker.net/user/Finish_openid_login');
		$_SESSION['OPENID_AUTH'] = false;
		$_SESSION['OPENID'] = $_POST['openid'];
		echo $url;
	}

	public function Finish_openid_login()
	{
		require_once "Auth/OpenID/Consumer.php";
		require_once "Auth/OpenID/FileStore.php";

		session_start();

		$store = new Auth_OpenID_FileStore('./oid_store');
		$consumer = new Auth_OpenID_Consumer($store);
		$response = $consumer->complete('http://dev.trezker.net/user/Finish_openid_login');

		if ($response->status == Auth_OpenID_SUCCESS) {
			$_SESSION['OPENID_AUTH'] = true;
		} else {
			$_SESSION['OPENID_AUTH'] = false;
		}
		if($_SESSION['OPENID_AUTH']) {
			echo "<a>yesy</a>";
			//echo '<a href="/user">Proceed</a>';
			$this->Login_openid($_SESSION['OPENID']);
			
//			header('Location: /user');
		} else {
			echo 'Denied!';
		}
	}

	/* Login openid is a private function
	 * Must only be called from Finish_openid_login which verifies the openid.
	 * */
	private function Login_openid($openid)
	{
		$this->Load_model('User_model');
		$r = $this->User_model->Login_openid($openid);
		if(is_array($r))
		{
			session_start();
			if(isset($_SESSION['userid']))
			{
				return 0;
			}
			$_SESSION['username'] = $r['Username'];
			$_SESSION['userid'] = $r['ID'];
			$_SESSION['admin'] = $this->User_model->User_has_access($_SESSION['userid'], 'Admin');
			header('Location: /user');
		} elseif($r == 'Not found') {
			$this->Sign_up();
		} else {
			echo 'TODO: openid login failure';
			//header('Location: /front')
		}
	}
	
	private function Sign_up() {
		include 'views/signup_view.php';
	}
	
	public function Create_user() {
		session_start();

		if($_SESSION['OPENID_AUTH'] !== true) {
			echo json_encode(array(success => false, reason => 'No authorized openid'));
			return;
		}
		if(!isset($_POST['username'])) {
			echo json_encode(array(success => false, reason => 'No username'));
			return;
		}

		$this->Load_model('User_model');
		$r = $this->User_model->Create_user_openid($_POST['username'], $_SESSION['OPENID']);
		if($r['success'] == false) {
			echo json_encode(array('success' => false, 'reason' => $r['reason']));
			return;
		} else {
			echo json_encode(array('success' => true));
			$_SESSION['username'] = $_POST['username'];
			$_SESSION['userid'] = $r['ID'];
			$_SESSION['admin'] = $this->User_model->User_has_access($_SESSION['userid'], 'Admin');
			return;
		}
	}
	
	public function Logout()
	{
		session_start();

		// Unset all of the session variables.
		$_SESSION = array();

		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}

		session_destroy();
		echo 1;
	}
}

?>
