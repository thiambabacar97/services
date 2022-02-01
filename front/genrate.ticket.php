<?php 
  include ('../../../inc/includes.php');
  require 'vendor/autoload.php';
  use Dompdf\Dompdf;
  use Dompdf\Options;
  $options = new Options();
  $options->set('isRemoteEnabled', true);
  $dompdf = new Dompdf($options);
  $ticket = new Ticket();
  $var = getVariablesItem( $_GET['id']);

  if (isset($_GET['id'])) {
      try { 
        $html='
          <div class="certificat" style="width:100%; margin:auto; padding:20px; text-align:center; border: 5px solid #787878">
              <br/>
              <span style="font-size:16px; font-weight:bold; border:2px solid; padding: 5px; marigin:auto">Facture du ticket</span>
              <br><br><br/><br/> 
              <div style="text-align:left;">
              <table style="border-collapse: collapse; margin: 0;width: 100%; font-family: "Trebuchet MS", Arial, Helvetica, sans-serif; text-align:left; ">
                <thead>
                  <tr>
                    <th style=" padding: 8px;border: 2px solid ">Description </th>
                    <th style=" padding: 8px;border: 2px solid ">Ticket</th>
                    <th style=" padding: 8px;border: 2px solid ">Technicien</th>
                    <th style=" padding: 8px;border: 2px solid ">Commentaires</th>
                    <th style=" padding: 8px;border: 2px solid ">Date</th>
                    <th style=" padding: 8px;border: 2px solid ">Date début de travail</th>
                    <th style=" padding: 8px;border: 2px solid ">Durée d\'intervention  </th>
                    <th style=" padding: 8px;border: 2px solid ">Montant </th>
                  </tr>
                </thead>
                <tbody>
                HTML';
                  while ($data = $var->next()) {
                    // print_r($data);
                    // return;
                    $ticket = new Ticket();
                    $id_ticket = $_GET["id"];
                    $type_support =1;
                    $ticket->getFromDB($id_ticket);
                    $id_specialite = $ticket->getField('itilcategories_id');
                    
                    $id_bill = getBillByTicket($id_ticket); 
            
                    $daily_montant = getDailyTaxAndMontantOfSupportType($id_specialite, $id_bill);
                    $montant =  ($daily_montant[0]['montant']) ? $daily_montant[0]['montant'] : ($daily_montant[0]['daily_tax'] * $data['temps_mis']) ;

                    $ticket=Dropdown::getDropdownName("glpi_tickets", $data['ticket_id']);
                    $technicien=Dropdown::getDropdownName("glpi_users", $data['technicien']);
                    
                    $html.= ' 
                      <tr>
                        <td style=" padding: 8px;border: 2px solid ">'. $data['description'].'</td>
                        <td style=" padding: 8px;border: 2px solid ">'.$ticket.'</td>
                        <td style=" padding: 8px; border: 2px solid ;">'. $technicien.'</td>
                        <td style=" padding: 8px; border: 2px solid ;">'. $data['comment'].'</td>
                        <td style="padding: 8px; border: 2px solid ;">'. $data['date'].'</td>
                        <td style=" padding: 8px; border: 2px solid ;">'. $data['date_beginning'].'</td>
                        <td style=" padding: 8px; border: 2px solid ;">'. $data['temps_mis'].'</td>
                        <td style=" padding: 8px; border: 2px solid ;">'. $montant.'</td>
                      </tr>
                      HTML';
                  }
                  $html.= '
                </tbody>
              </table>
            </div>
              <div style="width:95%; margin:auto">
                  <div style="text-align: right;  margin:0; padding: 0">Fait à .................... le .........................</div>
              </div>
              <br/><br/>
          </div>
          ';
        
        $dompdf->loadHtml($html, 'utf-8');
        $dompdf->setPaper('A3', 'landscape');
        $dompdf->render();
        $dompdf->stream("dompdf_out.pdf", array("Attachment" => 0));
      } catch (Exception $e) {
        echo json_encode(["status"=>"error", "message"=>$e->getMessage()]);  
      }
  }
  function getVariablesItem($id) {
    global $DB;
    global $DB;
    $iterator = $DB->request([
          'FROM'      => 'glpi_plugin_demobilling_timecards',
          'INNER JOIN' => [
            'glpi_ticket' => [
              'ON' => [
                    'glpi_ticket' => 'id',
                    'glpi_plugin_demobilling_timecards'    => 'ticket_id'
              ]
            ]
          ],
          'WHERE'     => [
            'glpi_plugin_demobilling_timecards.ticket_id' => $id
          ],
          'ORDER'    => 'id ASC'
    ]);

    $iterator = $DB->request([
          'FROM'      => 'glpi_plugin_demobilling_timecards',
          'WHERE'     => [
            'glpi_plugin_demobilling_timecards.ticket_id' => $id
          ],
          'ORDER'    => 'id ASC'
    ]);
    return $iterator;
}

function  getBillByTicket($ticket_id){
  global $DB;

  
  $iterator = $DB->request([
      'SELECT' => [
      'glpi_plugin_demobilling_billings.id'
      ],
      'FROM'      => 'glpi_tickets',
      'INNER JOIN' => [
          'glpi_itilcategories' => [
              'ON' => [
              'glpi_tickets' => 'itilcategories_id',
              'glpi_itilcategories'    => 'id'
              ]
          ],
          'glpi_plugin_demobilling_billings' => [
              'ON' => [
                  'glpi_itilcategories' => 'id',
                  'glpi_plugin_demobilling_billings'    => 'specialite'
              ]
          ],
      ],
      'WHERE'     => [
          'glpi_tickets.id' => $ticket_id,
      ],
      
      ]);

      $id_bill = "";
      while($data= $iterator->next()){
          $id_bill = $data['id']; 
      }
      return $id_bill;
};

function getDailyTaxAndMontantOfSupportType($specialite, $id_bill){
  /* 
  This method is use to get the daily_tax and the mootant of support_type
  param $support_type : the support_type that we need to get the daily_tax and support
  */

  global $DB;
  $sql = "SELECT `daily_tax`, `montant`
  FROM `glpi_plugin_demobilling_billings`
  WHERE `specialite` = $specialite
  AND `id` = $id_bill
  ";
  $iterator = $DB->request($sql);
  $daily_tax = [];
  while($datas = $iterator->next()){
      array_push($daily_tax, ['daily_tax' =>$datas['daily_tax'], 'montant'=>$datas['montant']]);
  }
  return $daily_tax;
 
}
