# [dolibarr_stocktransfers_module](https://github.com/caos30/dolibarr_stocktransfers_module)

## Current version: 1.18 [2021-02-07]

Compatible with Dolibarr v. 5.X-13.X

## Description

The objective of this Dolibarr's module is to offer a more friendly interface than the Dolibarr native to transfer products from one warehouse to another of the company that owns Dolibarr. If you have installed the "Purchases" module (also from IMASDEWEB) then in case of lack of stock for the transfer, a button appears to create a new process to purchase the missing stock.

The module allows the download of a PDF file with the list of products to be transferred and space below for the triple signature of the one who makes the shipment, the carrier and the one who will receive the products in the destination warehouse, usual "delivery note" in this type of transportation.

## Interface language translations

Until now: English / Catalan / Castillian (spanish) / Italian / German / French

## Slide presentation

[https://slides.com/caos30/dolibarr-stocktransfers-en](https://slides.com/caos30/dolibarr-stocktransfers-en)

## Initial author and history

Caos30 was the initial developer of this module, made for an specific customer on 2017. One year afterwards, caos30 decided in July 2018 to liberate the code of the module to make easy the contribution of other users, testers and developers. The final target is to be added to the core of the Dolibarr CMS, when the module be enough mature.

## Installation & user guide

The usual to any other module of Dolibarr.

Note: if you are updating your existing module -already using it- go to Settings > Modules and visit the settings of this module, and do at least one time a SAVE of settings with new configuration. It will preserve the existing options but it will probably add new ones.

Complete information at: https://imasdeweb.com/index.php?pag=m_blog&gad=detalle_entrada&entry=39

## Install new version of the module (upgrade)

Simply copy all the files of the module replacing the existing ones on /htdocs/custom/stocktransfers (recommended) or /htdocs/stocktransfers depending on where you installed it. You will need to deactivate && re-activate the module on Setup > Modules if it's mentioned in the CHANGELOG file for your update. Usually it is not needed, but it's recommended.

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
- https://slides.com/caos30/dolibarr-stocktransfers-en
- https://slides.com/caos30/dolibarr-stocktransfers
- User guide (english): https://imasdeweb.com/index.php?pag=m_blog&gad=detalle_entrada&entry=39
- Manual de usuario (castellano): https://imasdeweb.com/index.php?pag=m_blog&gad=detalle_entrada&entry=38

## Team

As developers & translators:

 - DEV & translation to CA, EN, ES: Sergi Rodrigues (from 2017)
 - Italian translation: Marco Civra (2019)
 - German translation: Hans-Dieter Schondelmaier (2020)
 - French translation: Laurent Lebleu (2020)

## Versions Log

See file **CHANGELOG.TXT** or see [CHANGELOG.TXT](https://github.com/caos30/dolibarr_stocktransfers_module/blob/master/CHANGELOG.TXT) file.
