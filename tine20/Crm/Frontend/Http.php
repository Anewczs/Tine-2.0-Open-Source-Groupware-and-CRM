<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Http.php 5090 2008-10-24 10:30:05Z p.schuele@metaways.de $
 */

/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the Crm application
 *
 * @package     Crm
 */
class Crm_Frontend_Http extends Tinebase_Application_Frontend_Http_Abstract
{
    protected $_applicationName = 'Crm';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Crm/js/Crm.js',
            'Crm/js/LeadEditDialog.js',
            'Crm/js/LeadState.js',
            'Crm/js/LeadSource.js',
            'Crm/js/LeadType.js',
            'Crm/js/Product.js',
            'Crm/js/Contact.js',
        );
    }
    
   	/**
     * export lead
     * 
     * @param	string JSON encoded string with lead ids for multi export
     * @param	format	pdf or csv or ...
     * 
     * @todo	implement csv/... export
     */
	public function exportLead($_leadIds, $_format = 'pdf')
	{
        $leadIds = Zend_Json::decode($_leadIds);
	    
        switch ($_format) {
		    case 'pdf':		        		        
                $pdf = new Crm_Export_Pdf();
		        
		        foreach ($leadIds as $leadId) {
                    $lead = Crm_Controller_Lead::getInstance()->get($leadId);
                    $pdf->generateLeadPdf($lead);
		        }
                    
                try {
                    $pdfOutput = $pdf->render();
                } catch ( Zend_Pdf_Exception $e ) {
                    Tinebase_Core::getLogger();
                    echo "could not create pdf <br/>". $e->__toString();
                    exit();            
                }
                
                header("Pragma: public");
                header("Cache-Control: max-age=0");
                header("Content-Disposition: inline; filename=lead.pdf"); 
                header("Content-type: application/x-pdf"); 
                echo $pdfOutput;            
                break;
                
		    default:
		        echo "Format $_format not supported yet.";
		        exit();
		}
	}    
}