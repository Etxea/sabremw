<?php

/*
 * This file is part of CalendR, a Fréquence web project.
 *
 * (c) 2012 Fréquence web
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Etxea;



/**
 * clase para la gestiń de Objectos en el CalDAV Sabre
 *
 * @author Jon Latorre Martinez <info@etxea.net>
 */
class SabreMW
{
    
    function __construct($db) {
        $this->db = $db;
         
    }
    
	/*
     * Funcion de prueba que devuelve hola mundo
     * return string
     */
    public function helloWorld()
    {
        return "Hola mundo!";
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
    public function addUser($username)
    {
        $principal_uri = 'principals/'.$username;
        //Ciframos la password
        // LO hemos pasado a la gestion de user de la app
        //$pass_md5 = md5($username.':SabreDAV:'.$password);
        /* $ret = 0;
        $ret = $this->db->insert('users',array('username'=>$username,'digesta1'=>$pass_md5));
        if ($ret!=1) {
            die("NO se ha podido insertar el user");
        }*/
        //Generamos el principal
        $ret = 0;
        $ret = $this->db->insert('principals',array('uri'=>$principal_uri,'displayname'=>$username));
        if ($ret!=1) {
            die("NO se ha podido insertar el principal");
        }
        //Generamos el calendario default
        $ret = 0;
        $ret = $this->db->insert('calendars',array('principaluri'=>$principal_uri,'displayname'=>'default','uri'=>'default','components'=>'VEVENT,VTODO'));        
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
    public function addEvent($calendar,$summary,$location,$fecha)
    {
        //echo "Vamos a crear $calendar,$summary,$location,$fecha ";
        $uid =  uniqid();
        $vcalendar = new \Sabre\VObject\Component\VCalendar();
        $event = $vcalendar->add('VEVENT', [
            'SUMMARY' => $summary,
            'DTSTART' => $fecha,
            'LOCATION' => $location,
            'UID' => $uid
        ]);
        
		$vcalendar->add($event);
        $etag = md5($vcalendar->serialize());
        $firstOccurence = $event->DTSTART->getDateTime()->getTimeStamp();
        $lastOccurence = $firstOccurence;
        $size = strlen($vcalendar->serialize());
        //echo $vcalendar->serialize();
        //echo "Vamos a introducir en BBDD el etag $etag el size $size y el tiempo $firstOccurence";
        $ret= $this->db->insert('calendarobjects',array('calendardata'=>$vcalendar->serialize(),'uri'=>$uid.".ics",'calendarid'=>$calendar,'etag'=>$etag,'size'=>$size,'componenttype'=>'VEVENT','firstoccurence'=>$firstOccurence,'lastoccurence'=>$lastOccurence));
        $id = $this->db->lastInsertId();
        
        //echo "Insercion hecha con ID: ".$id;
        //echo "Vamos a introducir en BBDD el etag $etag el size $size y el tiempo $firstOccurence";
        return $id;
    }
    public function delEvent($id)
    {
        $ret = $this->db->delete('calendarobjects', array('id' => $id));
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
    public function getUserCalendar($username) {
        $ret = $this->db->fetchAssoc('SELECT * FROM calendars WHERE principaluri = ?', array("principals/".$username));
        return $ret['id'];
    }
    
}
