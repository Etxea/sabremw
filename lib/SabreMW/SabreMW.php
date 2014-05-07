<?php

/*
 * This file is part of CalendR, a Fréquence web project.
 *
 * (c) 2012 Fréquence web
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SabreMW;



/**
 * clase para la gestiń de Objectos en el CalDAV Sabre
 *
 * @author Jon Latorre Martinez <info@etxea.net>
 */
class SabreMW
{
    
    function __construct($bd) {
        $this->bd = $bd;
         
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
		
		$event = \Sabre\VObject\Component::create('VEVENT');

		$event->SUMMARY = 'Curiosity launch';
		$event->DTSTART = '20111126T150202Z';
		$event->LOCATION = 'Cape Carnival';
		//echo "<h1>Evento</h1>";
		//echo "<pre>".$event->serialize()."</pre>";

		$vcalendar = new \Sabre\VObject\Component\VCalendar();
		$vcalendar->add($event);
		//echo "<h1>calendario</h1>";
		//echo "<pre>".$vcalendar->serialize()."</pre>";
		return $vcalendar->serialize();
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
}
