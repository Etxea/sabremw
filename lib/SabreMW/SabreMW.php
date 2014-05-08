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
    public function addUser($username,$password)
    {
        //Ciframos la password
        $pass_md5 = md5($username.':SabreDAV:'.$password);
        $db->insert('users',array('username'=>$data['username'],'digesta1'=>$pass_md5));
            // INSERT INTO calendars (principaluri, displayname, uri, description, components, ctag, transparent) VALUES
            // ('principals/admin','default calendar','default','','VEVENT,VTODO','1', '0');
    }
    public function addEvent($calendar,$summary,$location,$fecha)
    {
        //echo "Vamos a crear $calendar,$summary,$location,$fecha ";
        $uid =  uniqid();
        $event = \Sabre\VObject\Component::create('VEVENT');
		$event->SUMMARY = $summary;
        $event->DTSTART = $fecha;
        $event->LOCATION = $location;
        $event->UID = $uid;
        $vcalendar = new \Sabre\VObject\Component\VCalendar();
		$vcalendar->add($event);
        $etag = md5($vcalendar->serialize());
        $firstOccurence = $event->DTSTART->getDateTime()->getTimeStamp();
        $lastOccurence = $firstOccurence;
        $size = strlen($vcalendar->serialize());
        //echo $vcalendar->serialize();
        //echo "Vamos a introducir en BBDD el etag $etag el size $size y el tiempo $firstOccurence";
        $ret= $this->db->insert('calendarobjects',array('calendardata'=>$vcalendar->serialize(),'uri'=>$uid.".ics",'calendarid'=>1,'etag'=>$etag,'size'=>$size,'componenttype'=>'VEVENT','firstoccurence'=>$firstOccurence,'lastoccurence'=>$lastOccurence));
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
