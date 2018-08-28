# [dolibarr_stocktransfers_module](https://github.com/caos30/dolibarr_stocktransfers_module)

## Description

The objective of this Dolibarr's module is to offer a more friendly interface than the Dolibarr native to transfer products from one warehouse to another of the company that owns Dolibarr. If you have installed the "Purchases" module (also from IMASDEWEB) then in case of lack of stock for the transfer, a button appears to create a new process to purchase the missing stock.

The module allows the download of a PDF file with the list of products to be transferred and space below for the triple signature of the one who makes the shipment, the carrier and the one who will receive the products in the destination warehouse, usual "delivery note" in this type of transportation. 

## Interface language translations

Until now: English / Catalan / Castillian (spanish)

## Slide presentation

[https://slides.com/caos30/dolibarr-stocktransfers-en](https://slides.com/caos30/dolibarr-stocktransfers-en)

## Initial author and history

Caos30 was the initial developer of this module, made for an specific customer on 2017. One year afterwards, caos30 decided in July 2018 to liberate the code of the module to make easy the contribution of other users, testers and developers. The final target is to be added to the core of the Dolibarr CMS, when the module be enough mature. 

## Installation & user guide

The usual to any other module of Dolibarr. But also take in account that if you will use more than one currency on your Dolibarr then you need to apply a minor patch on Dolibarr core (at least on versions 5.x and 6.X).

Complete information at: https://imasdeweb.com/index.php?pag=m_blog&gad=detalle_entrada&entry=39

## License

LICENSE: GPL v3

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 3
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

## Links

- https://www.dolistore.com/de/moduleplugins/866-Stock-transfers.html
- http://slides.com/caos30/dolibarr-stocktransfers-en
- http://slides.com/caos30/dolibarr-stocktransfers
- User guide: https://imasdeweb.com/index.php?pag=m_blog&gad=detalle_entrada&entry=39
- Manual de usuario: https://imasdeweb.com/index.php?pag=m_blog&gad=detalle_entrada&entry=38

## Team

As developers & translators: 

 - DEV & translation to CA, EN, ES: Sergi Rodrigues (from 2017)
 
## Versions Log

== 1.1 [2017-11-20]

 + First version

== 1.2 [2017-11-29]

 + made compatible the module with /custom install
 + fixed some issues when not enabled Projects module

== 1.3 []

 + transfer edit page: easter egg to render the raw data of the transfer on database when double clicking a hidden link

== 1.4 [2018-07-03]

 + transfer_edit view: fixed an issue with dates, on days/months number lower than 10 (ie. 1/2/2018)

== 1.5 [2018-08-27]

 + pdf view: improved the header specifying departure & destination info.
 + improvements on english translations. also added new text labels.

## To do

 - ¿?
