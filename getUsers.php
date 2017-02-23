<?php
include 'connectdb.php';
//处理json中文乱码
function arrayRecursive($array, $function, $apply_to_keys_also = false)
{
    static $recursive_counter = 0;
    if (++$recursive_counter > 1000) {
        die('possible deep recursion attack');
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            arrayRecursive($array[$key], $function, $apply_to_keys_also);
        } else {
            $array[$key] = $function($value);
        }

        if ($apply_to_keys_also && is_string($key)) {
            $new_key = $function($key);
            if ($new_key != $key) {
                $array[$new_key] = $array[$key];
                unset($array[$key]);
            }
        }
    }
    $recursive_counter--;
}

//JSON编码输出
function JSON($array)
{
    arrayRecursive($array, 'urlencode', true);
    $json = json_encode($array);
    return urldecode($json);
}

//获取用户信息API，username为空的话，返回所有用户信息
function getAllInfo($username){
	$json = array("code"=>0, "msg"=>"success", "data"=>"");
	$sql_str = $username ? "SELECT * FROM userstable where username='".$username."'" : "SELECT * FROM userstable";	
	$tmp = query_sql($sql_str);
	if(empty($tmp)){
		$json["data"] = "用户数据为空";
		return JSON($json);
	}	
	$i = 0;
	while($row = mysql_fetch_array($tmp))
	{
		$result[$i] = array("id"=>$row[0], "name"=>$row[1], "sex"=>$row[2], "phone"=>$row[3], "address"=>$row[4], "password"=>$row[5], "username"=>$row[6], "email"=>$row[7]);
		$i++;
	}
	$json["data"] = $result;	
	return JSON($json);
}

//获取所有用户
function getUsers(){
	$json = array("code"=>0, "msg"=>"success", "data"=>"");
	$sql_str = "SELECT NAME, USERNAME, SEX FROM userstable";
	$tmp = query_sql($sql_str);
	if(empty($tmp)){
		$json["data"] = "用户数据为空";
		return JSON($json);
	}	
	$i = 0;
	while ($row = mysql_fetch_array($tmp))
	{
		$result[$i] = array("name"=>$row[0], "username"=>$row[1], "sex"=>$row[2]);
		$i++;
	}
	$json["data"] = $result;
	return JSON($json);
}

//登录
function login($username, $password){
	$json = array("code"=>0, "msg"=>"success", "data"=>"false");
	$sql_str = "SELECT PASSWORD FROM userstable where username='".$username."'";
	$tmp = query_sql($sql_str);
	if(empty($tmp)){
		$json["data"] = "不存在当前用户";
		return JSON($json);
	}	
	$row = mysql_fetch_array($tmp);
	if($password != $row[0]){
		$json["data"] = "用户名或密码不正确";	
		return JSON($json);	
	};
	$json["data"] = "true";
	session_start();
	if(!isset($_SESSION['username'])){
		$_SESSION['username'] = $username;
	}
	return JSON($json);
}

//录入用户
function insertUser($userinfo){
	$json = array("code"=>0, "msg"=>"success", "data"=>"");
	$userinfo = json_decode($userinfo);
	//判断非空字段是否有传
	$not_null_keys = array("username", "password");
	foreach ($not_null_keys as $nnk){
		if(!array_key_exists($nnk, $userinfo) || empty($userinfo->$nnk)){
			$json["data"] = "不能设置非空字段为空";
			return JSON($json);
		}
	}
	//判断用户是否已存在
	$sql_str = "SELECT PASSWORD FROM userstable where username='".$userinfo->username."'";
	$tmp = query_sql($sql_str);
	if(!empty($tmp)){
		$json["data"] = "用户已存在";
		return JSON($json);
	}
	//生成返回数据
	$i = 0;
	foreach ($userinfo as $k=>$v){
		$karray[$i] = $k;
		$varray[$i] = "'".$v."'";
		$i++;
	}
	$kstr = implode(',', $karray);
	$vstr = implode(',', $varray);
	$sql_str = "INSERT userstable (".$kstr.") VALUES (".$vstr.")";
	$tmp = query_sql($sql_str);
	if(empty($tmp)){
		$json["data"] = "录入失败";
		return JSON($json);
	}
	$json["data"] = "录入成功";
	return JSON($json);
}

//更新用户
function updateUser($id, $userinfo){
	$json = array("code"=>0, "msg"=>"success", "data"=>"false");
	$userinfo = json_decode($userinfo);
	//id为空则返回错误
	if(empty($id)){
		$json["data"] = "id不能为空";
		return JSON($json); 
	}
	//判断非空字段是否有传
	$not_null_keys = array("username", "password");
	foreach ($not_null_keys as $nnk){
		if(array_key_exists($nnk, $userinfo) && empty($userinfo->$nnk)){
			$json["data"] = "不能设置非空字段为空";
			return JSON($json);
		}
	}
	//判断用户是否已存在
	$sql_str = "SELECT PASSWORD FROM userstable where id=".$id;
	$tmp = query_sql($sql_str);
	if(empty($tmp)){
		$json["data"] = "用户不存在";
		return JSON($json);
	}
	//生成返回数据
	$i = 0;
	foreach ($userinfo as $k=>$v){
		$updatearray[$i] = $k."='".$v."'";
		$i++;
	}
	$updatestr = implode(',', $updatearray);
	$sql_str = "UPDATE userstable SET ".$updatestr." where id=".$id;
	$tmp = query_sql($sql_str);
	if(empty($tmp)){
		$json["data"] = "操作不成功";
		return JSON($json);
	}
	$json["data"] = "操作成功";
	return JSON($json);	
}

//删除用户
function deleteUser($id){
	$json = array("code"=>0, "msg"=>"success", "data"=>"false");
	//id为空则返回错误
	if(empty($id)){
		$json["data"] = "id不能为空";
		return JSON($json); 
	}
	//判断用户是否已存在
	$sql_str = "SELECT PASSWORD FROM userstable where id=".$id;
	$tmp = query_sql($sql_str);
	if(empty($tmp)){
		$json["data"] = "用户不存在";
		return JSON($json);
	}
	$sql_str = "delete from userstable where id=".$id;
	$tmp = query_sql($sql_str);
	if(empty($tmp)){
		$json["data"] = "操作不成功";
		return JSON($json);
	}
	$json["data"] = "操作成功";
	return JSON($json);
}

//API入口
$action = $_GET["action"];
switch ($action) {
	//获取用户信息GET方法
	case 'get_user_info':
		$username = urldecode($_GET["username"]);
		echo getAllInfo($username);
	;
	break;
	//获取所有用户GET方法
	case 'get_users':
		echo getUsers();
	;
	break;
	//登录GET方法
	case 'login':
		$username = urldecode($_GET["username"]);
		$password = urldecode($_GET["password"]);
		echo login($username, $password);
	;
	break;
	//录入用户信息post方法
	case 'insert_user':
		$userinfo = $_POST['userinfo'];
		echo insertUser($userinfo);
	;
	break;
	//更新用户信息post方法
	case 'update_user':
		$id = $_POST['id'];
		$userinfo = $_POST['userinfo'];
		echo updateUser($id, $userinfo);
	;
	break;
	//删除用户post方法
	case 'delete_user':
		$id = $_POST['id'];
		echo deleteUser($id);
	default:
		;
	break;
}
?> 