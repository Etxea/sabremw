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
 * Factory class for calendar handling
 *
 * @author Yohan Giarelli <yohan@giarel.li>
 */
class SabreMW
{
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
}
