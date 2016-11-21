<?php
    
	error_reporting(0);
	require_once("Rest.inc.php");
	
	class API extends REST {
	
		
		

		const DB_SERVER = "localhost";
		const DB_USER = "ubeed";
		const DB_PASSWORD = "20ubeed16";
		const DB = "triviaubeed";
		
		private $db = NULL;
		private $url_server="http://93.188.166.57/";
	
		public function __construct(){
			parent::__construct();				// Init parent contructor
			$this->dbConnect();					// Initiate Database connection
		}
		
		/*
		 *  Database connection 
		*/
		private function dbConnect(){
			$this->db = mysql_connect(self::DB_SERVER,self::DB_USER,self::DB_PASSWORD);
			if($this->db){
				mysql_select_db(self::DB,$this->db);
				mysql_query("SET NAMES 'utf8'");
			}else{
				exit("Error al conectar con DB.".mysql_error());
			}
				
		}
		
		/*
		 * Public method for access api.
		 * This method dynmically call the method based on the query string
		 *
		 */
		public function processApi(){
			$arrayRequest = explode("/", $_REQUEST['rquest']);
			$func = strtolower(trim($arrayRequest[1]));
            //$this->response($func,401);
			if((int)method_exists($this,$func) > 0){
				$this->$func();
				}
			else{
				$this->response('',404);				// If the method not exist with in this class, response would be "Page not found".
			}
		}

		private function prueba(){
			$response = array('success' => 'false', "msg" => "Invalid Email address or Password");
			$this->response(json_encode($response), 200);
		}
		
		function registrar_token_gcm() {
			try{

				//Solo se acepta el metodo POST para esta funcion
				if($this->get_request_method() != "POST"){
					$this->response('',406);
				}
				
				$token_gcm = $this->_request['gcm_token'];		
				$email = $this->_request['email'];
			

				$sql="UPDATE usuario SET token_gcm='$token_gcm' WHERE email='".$email."'";
				mysql_query($sql,$this->db);
					
				$response = array('success' => 'true', 'msg' => 'Se guardo token gcm correctamente.');
				$this->response(json_encode($response), 200);       
			
			}catch(Exception $e){
				$response = array('success' => 'false', 'msg' => $e);
				$this->response(json_encode($response), 400);

			}
		}

		public function get_detail_points(){

			$id_employed=$this->_request['id_employed'];


      		$sql='select d.*
        					 from detalle_puntos as d
        			    where d.empleado_id= '.$id_employed;

        	$result=mysql_query($sql,$this->db);

        	

        	$datos = array();
		    while ($array = mysql_fetch_array($result, MYSQL_ASSOC)) {
		    	
		    	$datos[]  = array('table_name' => $array['nombre_tabla'], 
		    						'table_id'=>$array['id_tabla'],
		    						'date' => $array['fecha'],
		    						'points'=>$array['puntos'],
		    						'id'=>$array['id']);
		    }



		    $response = array('success' => 'true', 'msg' => '', 'detail_points'=>$datos);
			$this->response(json_encode($response), 200);
        	
		}




		public function create_account(){
			$nombre = $this->_request['name'];
			$apellido = $this->_request['last_name'];
			$email=$this->_request['email'];
			$contrasenia = $this->_request['password'];
			$contrasenia_encriptado = sha1($contrasenia);
			$url_imagen=$this->_request['imagen'];


			//Comprobamos si el usuario ya existe
			$sql="select * from usuario where email='$email'";
			$result=mysql_query($sql,$this->db);
			if($result){
				$count=mysql_num_rows($result);
				if($count>0){
					$response = array('success' => 'false', 'msg' => 'Error. El usuario ya existe.');
					$this->response(json_encode($response), 200);
				}

			}

			mysql_query("START TRANSACTION", $this->db);

			$fecha=date("Y-m-d H:i:s");
			$sql="insert into usuario(email, apellido, nombre, contrasenia, imagen, fecha_creacion)
								values('$email', 
									'$apellido', 
									'$nombre', 
									'$contrasenia_encriptado',
									'$url_imagen',
									'$fecha')";
			$result=mysql_query($sql,$this->db);
			if($result){
			
				mysql_query("COMMIT", $this->db);
				$response = array('success' => 'true', 'msg' => 'Usuario creado correctamente.');
				$this->response(json_encode($response), 200);
		
			}else{
				mysql_query("ROLLBACK", $this->db);
				$response = array('success' => 'false', 'msg' => 'Error al crear usuario.');
				$this->response(json_encode($response), 200);
			}

		}

		public function send_email(){
			$email=$this->_request['email'];
			//Comprobamos si el usuario existe
			$sql="select * from usuario where usuario='$email'";
			$result=mysql_query($sql,$this->db);
			if($result){
				$count=mysql_num_rows($result);
				if($count>0){
					$row=mysql_fetch_assoc($result);
					$id_user=$row['id'];
				}else{
					$response = array('success' => 'false', 'msg' => 'Error. El usuario no existe.');
					$this->response(json_encode($response), 200);
				}

			}
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		    $charactersLength = strlen($characters);
		    $randomString = '';
		    for ($i = 0; $i < 6; $i++) {
		        $randomString .= $characters[rand(0, $charactersLength - 1)];
		    }
			$pass=sha1($randomString);

			//Aca enviamos un email con un password nuevo
			require_once('PHPMailer/class.phpmailer.php');
			$emailSender = new PHPMailer();
    		//$emailSender->From      = 'nexo@nexosoluciones.com.ar ';
    		$emailSender->From      = 'jeremiaschaparro@yahoo.com.ar ';
    		$emailSender->FromName  = 'CPC';
    		$emailSender->Subject   = 'Restableciendo contraseña de usuario.';
    
		    $body= "<font size=2>Hola,<br>
		            Su nueva contraseña es: ".$randomString.".<br>
		            Luego de ingresar a la App CPC, puede modificarla en la sección de configuración.<br>
		            Gracias.</font>";

		    $emailSender->Body = $body;
		    //$email->AddAddress( "jeremiaschaparro@gmail.com" );
		    $emailSender->AddAddress( $email );

		    $emailSender->IsHTML(true);
		    
		    $result=$emailSender->Send();
        
			if ($result) {
				
				$sql="update usuario set contrasenia='$pass' where usuario='$email'";
				mysql_query($sql,$this->db);
				$response = array('success' => 'true', 'msg' => 'Contraseña reestablecida.');
				$this->response(json_encode($response), 200);
			}
			$response = array('success' => 'false', 'msg' => 'Error. No se envio email.');
			$this->response(json_encode($response), 200);
		}

		public function get_datos_home(){
			$email = $this->_request['email'];

			$array_partidas_iniciadas = array();
			$array_partidas_terminadas = array();

			//Comprobamos si el usuario existe
			$sql="select * from usuario where email='$email'";
			$result=mysql_query($sql,$this->db);
			if($result){
				$count=mysql_num_rows($result);
				if($count>0){
					$row=mysql_fetch_assoc($result);
					$id_user=$row['id'];
					$voucher =$row['voucher'];
					$puntos = $row['puntos_acumulados']; 
				}else{
					$response = array('success' => 'false', 'msg' => 'Error. El usuario no existe.');
					$this->response(json_encode($response), 200);
				}


				$sql="select d.id, d.terminado, d.fecha_actualizacion, 
				      d.fecha_creacion, u3.email as email_turno, 
				      CONCAT(u1.nombre,' ',u1.apellido) as nombre1, 
				      CONCAT(u2.nombre,' ',u2.apellido) as nombre2,
				      u1.id as id1,
				      u2.id as id2,
				      u1.imagen as imagen1,
				      u2.imagen as imagen2
				    from duelo as d 
				    left join usuario as u1 on u1.id=d.usuario1_id 
				    left join usuario as u2 on u2.id=d.usuario2_id 
				    left join usuario as u3 on u3.id=d.usuario_id_turno
				    where d.activo=1 and (d.usuario1_id=$id_user or d.usuario2_id=$id_user) 
				    order by fecha_actualizacion desc ";
				$result=mysql_query($sql,$this->db);
				if($result){
					while ($array = mysql_fetch_array($result, MYSQL_ASSOC)) {
						
						$nombre1 = $array['nombre1'];
						$nombre2 = $array['nombre2'];
						$imagen1 = $array['imagen1'];
						$imagen2 = $array['imagen2'];
						$terminado = $array['terminado'];
						$email_turno = $array['email_turno'];
						$id1 = $array['id1'];
						$id2 = $array['id2'];
						$duelo_id = $array['id'];
						$fecha_actualizacion = $array['fecha_actualizacion'];
						$respuestas = 0;
						$respuestas_oponente = 0;

						$nombre = "";
						$imagen = "";


						$id_duelo = $array['id'];

						//Verificamos la cantidad de preguntas respondidas correctamente para cada usuario
						
						$sql = "select id as cantidad from detalle_puntos 
								where usuario_id=$id1 and duelo_id=$id_duelo";
						$result2=mysql_query($sql,$this->db);
						
						$sql = "select id as cantidad from detalle_puntos 
								where usuario_id=$id2 and duelo_id=$id_duelo";
						$result3=mysql_query($sql,$this->db);
						

						if($id_user!=$id1){
							$respuestas = mysql_num_rows($result2);
							$respuestas_oponente = mysql_num_rows($result3);
							$nombre = $nombre1;
							$imagen = $imagen1;
						}else{
							$respuestas = mysql_num_rows($result3);
							$respuestas_oponente = mysql_num_rows($result2);
							$nombre = $nombre2;
							$imagen = $imagen2;
						}

						$datos = array('nombre_oponente'=>$nombre,
										'imagen_oponente'=>$imagen,
										'terminado'=>$terminado,
										'respuestas'=>$respuestas,
										'respuestas_oponente'=>$respuestas_oponente,
										'fecha_actualizacion'=>$fecha_actualizacion,
										'email_turno'=>$email_turno,
										'duelo_id'=>$duelo_id);
						if($terminado==1){
							$array_partidas_terminadas[]=$datos;
						}else{
							$array_partidas_iniciadas[]=$datos;
						}






						
					}


					//Agregamos los otros datos que faltan a la respuesta
					$response = array('success' => 'true', 'msg' => 'Datos obtenidos correctamente',
										'puntos'=> $puntos, 'voucher'=>$voucher,
										'partidas_iniciadas'=>$array_partidas_iniciadas,
										'partidas_terminadas'=>$array_partidas_terminadas);
					$this->response(json_encode($response), 200);

				}

			}





			$response = array('success' => 'false', 'msg' => 'Error. El usuario no existe.');
					$this->response(json_encode($response), 200);

		}

		public function config_account(){
			$name = $this->_request['nombre'];
			$last_name = $this->_request['apellido'];
			$email=$this->_request['email'];
			$url_image=$this->_request['url_image'];
			$notificaciones = $this->_request['notificaciones'];
			$sonidos = $this->_request['sonidos'];

		

			//Comprobamos si el usuario existe
			$sql="select * from usuario where email='$email'";
			$result=mysql_query($sql,$this->db);
			if($result){
				$count=mysql_num_rows($result);
				if($count>0){
					$row=mysql_fetch_assoc($result);
					$id_user=$row['id'];
				}else{
					$response = array('success' => 'false', 'msg' => 'Error. El usuario no existe.');
					$this->response(json_encode($response), 200);
				}

			}

			mysql_query("START TRANSACTION", $this->db);

	

			$sql="update usuario set
						apellido='$last_name',
						nombre='$name',
						imagen='$url_image',
						notificaciones = $notificaciones,
						sonidos = $sonidos ";
	
		     $sql.=" where email='$email'";

			$result=mysql_query($sql,$this->db);
			if($result){
			
				
		
			
				mysql_query("COMMIT", $this->db);
				$response = array('success' => 'true', 'msg' => 'Usuario actualizado correctamente.');
				$this->response(json_encode($response), 200);
				
			}else{
				mysql_query("ROLLBACK", $this->db);
				$response = array('success' => 'false', 'msg' => 'Error al actualizar usuario.');
				$this->response(json_encode($response), 200);
			}

		}

		public function login() {

        $usuario = $this->_request['usuario'];
        $contrasenia = $this->_request['contrasenia'];	
        $usuario_facebook =  $this->_request['usuario_facebook'];
        
        $contrasenia = sha1($contrasenia);

        if(empty($usuario)){
        	$sql = "
            SELECT 
                    *
            FROM 
                    usuario AS u 
            WHERE 
                    u.usuario_facebook = '$usuario_facebook'";

        }else{
        	$sql = "
            SELECT 
                    *
            FROM 
                    usuario AS u 
            WHERE 
                    u.email = '$usuario' AND
                    u.contrasenia = '$contrasenia'";
        }

        

        $result=mysql_query($sql,$this->db);
        if ($result) {

        	$count=mysql_num_rows($result);
        	if($count==0){
        		$response = array('success' => 'false', 'msg' => 'Usuario o contrasenia incorrecto.');
				$this->response(json_encode($response), 200);
        	}

        	$row = mysql_fetch_assoc($result);

        

        	$datos = array('id' => $row['id'] ,
        					'nombre' => $row['nombre'],
        					'apellido' => $row['apellido'],
        					'email' => $row['email'],
        					'imagen' => $row['imagen'],
        					'usuario_facebook' => $row['usuario_facebook'],
        					'notificaciones' => $row['notificaciones'],
        					'sonidos' => $row['sonidos']);


            $response = array('success' => 'true', 'usuario'=>$datos, 'msg' => 'Login correcto');
			$this->response(json_encode($response), 200);
        } else {
            $response = array('success' => 'false', 'msg' => 'Usuario o contrasenia incorrecto.');
			$this->response(json_encode($response), 200);
        }
    }


    	public function loginFacebook() {

        $email = $this->_request['email'];
        $nombre = $this->_request['nombre'];
        $apellido = $this->_request['apellido'];	
        $usuario_facebook =  $this->_request['usuario_facebook'];
         $token =  $this->_request['token'];
         $imagen = $this->_request['imagen'];
        
        $contrasenia = sha1($contrasenia);

     
    	$sql = "
        SELECT 
                *
        FROM 
                usuario AS u 
        WHERE 
                u.email = '$email'";
        

        $result=mysql_query($sql,$this->db);
        $row = mysql_fetch_assoc($result);
        if ($result) {

        	$count=mysql_num_rows($result);
        	if($count==0){
        		$fecha=date("Y-m-d H:i:s");
	        	$url_imagen="";
				$sql="insert into usuario(email, apellido, nombre, usuario_facebook, contrasenia, imagen, fecha_creacion)
									values('$email', 
										'$apellido', 
										'$nombre', 
										'$usuario_facebook',
										'$token',
										'$imagen',
										'$fecha')";
				$result=mysql_query($sql,$this->db);
				if($result){

					$datos = array('id' => mysql_insert_id(),
        					'nombre' => $nombre,
        					'apellido' => $row['apellido'],
        					'email' => $email,
        					'imagen' => $imagen,
        					'usuario_facebook' =>$usuario_facebook,
        					'notificaciones' => 'true',
        					'sonidos' => 'true');


					$response = array('success' => 'true', 'usuario'=>$datos, 'msg' => 'Login correcto');
						$this->response(json_encode($response), 200);
				}else{
	            	$response = array('success' => 'false', 'msg' => 'Usuario incorrecto.');
					$this->response(json_encode($response), 200);
	        	}
        	}

        	
        	$datos = array('id' => $row['id'] ,
        					'nombre' => $row['nombre'],
        					'apellido' => $row['apellido'],
        					'email' => $row['email'],
        					'imagen' => $row['imagen'],
        					'usuario_facebook' => $row['usuario_facebook'],
        					'notificaciones' => $row['notificaciones'],
        					'sonidos' => $row['sonidos']);
            $response = array('success' => 'true', 'usuario'=>$datos, 'msg' => 'Login correcto');
			$this->response(json_encode($response), 200);
        } else {

        	

					$response = array('success' => 'false','msg' => 'Usuario incorrecto.');
					$this->response(json_encode($response), 200);
        	
        }
    }


    public function verificate_answer(){
    		$idEmpleado=$this->_request['empleado_id'];
    		$idOptionQuestion=$this->_request['option_question_id'];
    		$points=$this->_request['points_question'];

    		$sqlAnswer="insert into respuesta_trivia(opcion_pregunta_id, empleado_id) values(".$idOptionQuestion.", ".$idEmpleado.")";
    		mysql_query($sqlAnswer,$this->db);

    		$sql="select * from opcion_pregunta where id=".$idOptionQuestion;

    		$result=mysql_query($sql,$this->db);
        	$row=mysql_fetch_assoc($result);
        	if($row['correcta']==true){
        		$response = array('success' => 'true', 'msg' => 'Respuesta correcta!', 'points'=>$points, 'correct'=>'true');
				$this->response(json_encode($response), 200);
        	}else{
        		$response = array('success' => 'true', 'msg' => 'Respuesta incorrecta!', 'points'=>0, 'correct'=>'false');
				$this->response(json_encode($response), 200);
        	}


    }

    public function get_trivias(){

			$idSector=$this->_request['id_sector'];

			$sql="select t.*, u.nombre as unidad_medida from 
					trivia_detalle as td 
					inner join trivia as t on t.id=td.trivia_id
					left join unidad_medida as u on u.id=t.unidad_medida_id
					where t.activa=true and td.sector_id=".$idSector;

			$result=mysql_query($sql,$this->db);
			
			$datos = array();
			while ($array = mysql_fetch_array($result, MYSQL_ASSOC)) {

				$datos[]  = array('id' => $array['id'], 
		    						'name'=>$array['nombre'],
		    						'init_date'=>$array['fechaInicio'],
		    						'measurement_unit' => $array['unidad_medida']);

			}

			$response = array('success' => 'true', 'msg' => 'Se obtuvieron trivias', 'trivias'=>$datos);
			$this->response(json_encode($response), 200);
      		
        	
	}


	public function get_trivia(){
		$idCategoria=$this->_request['categoria_id'];
		$idUsuario=$this->_request['usuario_id'];

		$sql="select * from trivia where categoria_id=$idCategoria and activa=1";
		$result=mysql_query($sql,$this->db);

		$count = mysql_num_rows($result);
		$index = rand(0, ($count-1));

			
		$datos = array();
		while ($array = mysql_fetch_array($result, MYSQL_ASSOC)) {

			$datos[]  = array('id' => $array['id'], 
	    						'name'=>$array['nombre'],
	    						'points'=>$array['puntos'],
	    						'options_question'=>$this->get_question_option($array['id']));

		}

		$response = array('success' => 'true', 'msg' => 'Se obtuvieron preguntas', 'trivia'=>$datos[$index]);
		$this->response(json_encode($response), 200);


	}

	private function get_question_option($trivia_id){

		$sql="select * from opcion_trivia
				where trivia_id=".$trivia_id;
		$result=mysql_query($sql,$this->db);
			
		$datos = array();
		while ($array = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$datos[] = array('id' => $array['id'],
							'name' => $array['descripcion'],
							'correct'=> $array['correcta']);
		}

		return $datos;


	}

    public function upload_image(){


			// direcciones de los archivos de las imagenes
			$target_dir_uploads = "imagenes_ubeed/";

			//$this->url_server="http://190.188.1.235/testing/";

			// variables para recibir al momento de update una imagen
			$email=$this->_request['email'];

			// Aqui modifica la imagen del usuario con el usuario_numero,empresa

		  	if(isset($email) && isset($_FILES['image'])){

    			$direccion_image_usuario = $target_dir_uploads.$email.basename($_FILES["image"]["name"]);
    			$direccion = $this->url_server.$direccion_image_usuario;

    			if(move_uploaded_file($_FILES['image']['tmp_name'],"../".$direccion_image_usuario)){

		 	   		
		            $sql="update usuario set imagen='".$direccion."' where email='$email'";
		            $result=mysql_query($sql,$this->db);
		            if($result){
		            	$response = array('success' => 'true', 'url'=>$direccion,'msg' => 'Imagen guardada correctamente');
						$this->response(json_encode($response), 200);
		            }

				}else{
					$response = array('success' => 'false', 'url'=>'','msg' => 'Imagen no cargada');
					$this->response(json_encode($response), 200);
				}
		                
		         
		       

		 	}else{
				//Imagen no cargada
				$response = array('success' => 'false', 'url'=>'','msg' => 'Datos vacios');
								$this->response(json_encode($response), 200);
			}
	
    }

		
		/*
		 *	Encode array into JSON
		*/
		private function json($data, $idArray="objetos"){
			if(is_array($data)){
				return json_encode(array($idArray => $data));
			}
		}
	}


	
	// Initiiate Library
	
	$api = new API;
	$api->processApi();
?>