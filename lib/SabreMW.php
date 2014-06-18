<?php

/**
 * clase para la gestiń de Objectos en el CalDAV Sabre
 *
 * @author Jon Latorre Martinez <info@etxea.net>
 */
class SabreMW
{
    
    function __construct($db) {
        $this->db = $db;
        $this->default_calendar = 'uroges';
         
    }
    
    /*
     * Funcion de prueba que devuelve hola mundo
     * return string
     */
    public function test()
    {
        return True;
    }
    /*
     * Funcion de prueba que genera un EVENTO y un VCALENDAR que lo envuelve
     * return string
     */
    public function testEvento()
    {
        $ret = $this->addEvent("admin",'Curiosity launch','Cape Carnival','20140508');
        echo "Creado el evento ".$ret;
        if ( $this->delEvent($ret) )
            echo "Evento borrado";
		
	}
    
    /*
     * 
     */
    public function addUser($username,$password)
    {
        //$principal_uri = 'principals/'.$username;
        //Ciframos la password
        $passwordHasher = new Hautelook\Phpass\PasswordHash(8,false);
        $passwordsalt = '9921b26e612100af3e9f67cdfbc0f5';
        $pass_md5 = $passwordHasher->HashPassword($password . $passwordsalt);
        $ret = 0;
        $ret = $this->db->insert('oc_users',array('uid'=>$username,'password'=>$pass_md5));
        if ($ret!=1) {
            die("NO se ha podido insertar el user");
        }
        /*
        //Generamos el principal
        $ret = 0;
        $ret = $this->db->insert('principals',array('uri'=>$principal_uri,'displayname'=>$username));
        if ($ret!=1) {
            die("NO se ha podido insertar el principal");
        }
        */
        //Generamos el calendario default
        $ret = 0;
        $ret = $this->db->insert('oc_clndr_calendars',
            array('userid'=>$username,'displayname'=>$this->default_calendar,
                'uri'=>$this->default_calendar,'active'=>1,'ctag'=>1,'components'=>'VEVENT,VTODO'));        
        if ($ret!=1) {
            die("NO se ha podido insertar el calendar");
        }
    }
    
    /*
    * Esta función borra el principal y los calendarios del usuario proporcionado
    */
    public function delUser($username)
    {
        echo "Vamos a borrar el usuario $username";
        
        //Obtenemos los calendarios
        $calendars = $this->db->fetchAll('SELECT id FROM oc_clndr_calendars WHERE userid = ?',array($username));
        foreach($calendars as $calendar) {
            //Borramos los eventos
            $this->db->delete('oc_clndr_objects',array('calendarid'=>$calendar['id']));
            //Borramos el calendario
            $this->db->delete('oc_clndr_calendars',array('userid'=>$username));
        }
        $this->db->delete('oc_users',array('uid'=>$username));
    }
    
    public function getEvent($id) {
        //echo "Buscamos el evento con id ".$id."<br>";
        $ret = $this->db->fetchAssoc('SELECT * FROM calendarobjects WHERE id = ?', array($id));
        //var_dump($ret);
        return $ret;
    }
    
    public function addEvent($calendar,$titulo,$descripcion,$fecha)
    {
        //FIXME esto debería ser una variable
        $location="Uroges Cruces";
        echo "Vamos a crear $calendar,$titulo,$descripcion,$fecha ";
        $uid =  strtoupper(\Rhumsaa\Uuid\Uuid::uuid4());
        $inicio = new \DateTime($fecha);
        $inicio = "VALUE=DATE:".$fecha;
        //$inicio = $inicio->setTimezone(new DateTimezone("europe/madrid")); 
        $now = new DateTime("now"); 
        //$now = $now->setTimezone(new DateTimezone("europe/madrid")); 
        $vcalendar = new \Sabre\VObject\Component\VCalendar();
        $event = $vcalendar->add('VEVENT', [
            'CREATED' => $now,
            'UID' => $uid,
            'SUMMARY' => $titulo,
            'DESCRIPTION' => $descripcion,
            'DTSTART' => $fecha,
            // 'LOCATION' => $location,
            
            'DTSTAMP' => $now,
            
            'LAST-MODIFIED'=> $now,
            'TRANSP' => "TRANSPARENT",
            'SEQUENCE' => 0
            
        ]);
        
		//$vcalendar->add($event);
        $etag = md5($vcalendar->serialize());
        $startdate = $event->DTSTART->getDateTime()->getTimeStamp();
        $enddate = $startdate;
        $size = strlen($vcalendar->serialize());
        //echo "Tenemos el vclandera:<pre>";
        //echo $vcalendar->serialize();
        //echo "</pre>";
        
        //echo "Vamos a introducir en BBDD el etag $etag el size $size y el tiempo $startdate";
        $ret= $this->db->insert('oc_clndr_objects',array('calendardata'=>$vcalendar->serialize(),
            'uri'=>$uid.".ics",'calendarid'=>$calendar,
            'startdate'=>$startdate,'enddate'=>$enddate,'lastmodified'=>$now->getTimestamp()));
        $id = $this->db->lastInsertId();
       	//Actualizamos el ctag del calendario
        $this->updateSyncToken($calendar);
        
        //echo "Insercion hecha con ID: ".$id;
        //echo "Vamos a introducir en BBDD el etag $etag el size $size y el tiempo $startdate";
        return $id;
    }
    
    public function delEvent($id)
    {
        $event = $this->db->fetchAssoc('SELECT * FROM oc_clndr_objects WHERE id = ?', array($id));
        $ret = $this->db->delete('oc_clndr_objects', array('id' => $id));
        //Actualizamos el ctag del calendario
        $this->updateSyncToken($event['calendarid']);
        if ($ret == 1) 
        {  
            //A borrado 1 columna 
            echo "Borrado OK";
            return 1;
        }
        else
        {
            echo "Borrado KO";
            return 0;
        }
        
    }
    
    public function updateSyncToken($calendar_id) {
        $calendar = $this->db->fetchAssoc('SELECT * FROM oc_clndr_calendars WHERE id = ?', array($calendar_id));
        //var_dump($calendar);
        echo "Vamos a actualizar el ctag de ".$calendar['userid']." que ahora es". $calendar['ctag']." ---> ";
        $new_ctag = $calendar['ctag']+1;
        $ret = $this->db->update('oc_clndr_calendars',array('ctag'=>$new_ctag),array('id'=>$calendar_id)); 
        if ( $ret ==1 ) {
            echo "Ctag actualizado a ".$new_ctag." con resultado".$ret."<br>";
            return 1;
        } else {
            echo "Fallo actualizando ctag";
            return -1;
        }
        
    }
    
    public function getUserCalendar($username) {
        $ret = $this->db->fetchAssoc('SELECT * FROM oc_clndr_calendars WHERE userid = ?', array($username));
        return $ret['id'];
    }
    
}
