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
        $principal_uri = 'principals/'.$username;
        //Ciframos la password
        $pass_md5 = md5($username.':SabreDAV:'.$password);
        $ret = 0;
        $ret = $this->db->insert('users',array('username'=>$username,'digesta1'=>$pass_md5));
        if ($ret!=1) {
            die("NO se ha podido insertar el user");
        }
        //Generamos el principal
        $ret = 0;
        $ret = $this->db->insert('principals',array('uri'=>$principal_uri,'displayname'=>$username));
        if ($ret!=1) {
            die("NO se ha podido insertar el principal");
        }
        //Generamos el calendario default
        $ret = 0;
        $ret = $this->db->insert('calendars',
            array('principaluri'=>$principal_uri,'displayname'=>$this->default_calendar,
            'synctoken'=>uniqid(),'uri'=>$this->default_calendar,'components'=>'VEVENT,VTODO'));        
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
        $principal_uri = 'principals/'.$username;
        //Obtenemos los calendarios
        $calendars = $this->db->fetchAll('SELECT id FROM calendars WHERE principaluri = ?',array($principal_uri));
        foreach($calendars as $calendar) {
            //Borramos los eventos
            $this->db->delete('calendarobjects',array('calendarid'=>$calendar['id']));
            //Borramos el calendario
            $this->db->delete('calendars',array('id'=>$calendar['id']));
        }
        //Borramos de principals
        $this->db->delete('principals',array('uri'=>$principal_uri));
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
        $firstOccurence = $event->DTSTART->getDateTime()->getTimeStamp();
        $lastOccurence = $firstOccurence;
        $size = strlen($vcalendar->serialize());
        //echo "Tenemos el vclandera:<pre>";
        //echo $vcalendar->serialize();
        //echo "</pre>";
        
        //echo "Vamos a introducir en BBDD el etag $etag el size $size y el tiempo $firstOccurence";
        $ret= $this->db->insert('calendarobjects',array('calendardata'=>$vcalendar->serialize(),'uri'=>$uid.".ics",'calendarid'=>$calendar,'etag'=>$etag,
            'size'=>$size,'componenttype'=>'VEVENT','firstoccurence'=>$firstOccurence,'lastoccurence'=>$lastOccurence,'lastmodified'=>$now->getTimestamp()));
        $id = $this->db->lastInsertId();
       	//Actualizamos el synctoken del calendario
        $this->updateSyncToken($calendar);
        
        //echo "Insercion hecha con ID: ".$id;
        //echo "Vamos a introducir en BBDD el etag $etag el size $size y el tiempo $firstOccurence";
        return $id;
    }
    
    public function updateEvent($event_id,$calendar,$titulo,$descripcion,$fecha)
    {
        //FIXME esto debería ser una variable
        $location="Uroges Cruces";
        //echo "leemos el evento previo<br>";
        //$evento = $this->db->fetchAssoc('SELECT * FROM calendarobjects WHERE id = ?', array($event_id));
        //var_dump($evento);
        //Borramos el evento previo
        $ret = $this->db->delete('calendarobjects', array('id' => $event_id));
        echo "Borrado evento previo con id ".$event_id." y resultado ".$ret;
        //echo "Vamos a crear $calendar,$summary,$location,$fecha ";
        $uid =  \Rhumsaa\Uuid\Uuid::uuid4();
        $vcalendar = new \Sabre\VObject\Component\VCalendar();
        $event = $vcalendar->add('VEVENT', [
            'SUMMARY' => $titulo,
            'DESCRIPTION' => $descripcion,
            'DTSTART' => new \DateTime($fecha),
            'LOCATION' => $location,
            'UID' => $uid
        ]);
        
		//$vcalendar->add($event);
        $etag = md5($vcalendar->serialize());
        $firstOccurence = $event->DTSTART->getDateTime()->getTimeStamp();
        $lastOccurence = $firstOccurence;
        $size = strlen($vcalendar->serialize());
        
        //echo "Tenemos el vclandera:<pre>";
        //echo $vcalendar->serialize();
        //echo "</pre>";
        
        //echo "Vamos a introducir en BBDD el etag $etag el size $size y el tiempo $firstOccurence";
        $ret= $this->db->insert('calendarobjects',array('id'=>$event_id,'calendardata'=>$vcalendar->serialize(),'uri'=>$uid.".ics",'calendarid'=>$calendar,'etag'=>$etag,
            'size'=>$size,'componenttype'=>'VEVENT','firstoccurence'=>$firstOccurence,'lastoccurence'=>$lastOccurence,'lastmodified'=>$now->format('c')));

       	//Actualizamos el synctoken del calendario
        $this->updateSyncToken($calendar);
        //echo "Insercion hecha con ID: ".$event_id;
        //echo "Vamos a introducir en BBDD el etag $etag el size $size y el tiempo $firstOccurence";
        return $event_id;
    }
    
    public function delEvent($id)
    {
        $event = $this->db->fetchAssoc('SELECT * FROM calendarobjects WHERE id = ?', array($id));
        $ret = $this->db->delete('calendarobjects', array('id' => $id));
        //Actualizamos el synctoken del calendario
        $this->updateSyncToken($event['calendarid']);
        if ($ret == 1) 
        {  
            //A borrado 1 columna 
            return 1;
        }
        else
        {
            return 0;
        }
        
    }
    
    public function updateSyncToken($calendar_id) {
        $calendar = $this->db->fetchAssoc('SELECT * FROM calendars WHERE id = ?', array($calendar_id));
        //var_dump($calendar);
        echo "Vamos a actualizar el synctoken de ".$calendar['principaluri']." qu ahora es". $calendar['synctoken']." --- ";
        $new_synctoken = $calendar['synctoken']+1;
        $ret = $this->db->update('calendars',array('synctoken'=>$new_synctoken),array('id'=>$calendar_id)); 
        if ( $ret ==1 ) {
            echo "Synctoken actualizado a ".$new_synctoken." con resultado".$ret."<br>";
            return 1;
        } else {
            echo "Fallo actualizando synctoken";
            return -1;
        }
        
    }
    
    public function getUserCalendar($username) {
        $ret = $this->db->fetchAssoc('SELECT * FROM calendars WHERE principaluri = ?', array("principals/".$username));
        return $ret['id'];
    }
    
}
