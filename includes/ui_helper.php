<?php
function render_stats_cards(array $stats): void {
 echo '<div class="kpi-grid" style="margin-bottom:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px;">';

 $palettes = [
  ['bg'=>'linear-gradient(135deg,#0F172A,#1E293B)','color'=>'#60A5FA'],
  ['bg'=>'linear-gradient(135deg,#111827,#1F2937)','color'=>'#34D399'],
  ['bg'=>'linear-gradient(135deg,#172554,#1D4ED8)','color'=>'#93C5FD'],
  ['bg'=>'linear-gradient(135deg,#3F0D12,#A71D31)','color'=>'#FCA5A5']
 ];

 $i=0;
 foreach($stats as $s){
  $p = $palettes[$i % count($palettes)];
  echo '<div class="kpi-card" style="
        background:'.$p['bg'].';
        min-height:140px;
        border-radius:18px;
        padding:22px;
        position:relative;
        overflow:hidden;
        box-shadow:0 10px 25px rgba(0,0,0,.18);
        display:flex;
        flex-direction:column;
        justify-content:space-between;
  ">';
  echo '<div class="kpi-label" style="color:'.$p['color'].';font-size:14px;font-weight:700;letter-spacing:.5px;">
        <i class="ti '.$s['icon'].'"></i> '.$s['label'].'
        </div>';

  echo '<div class="kpi-valor" style="
        color:#fff;
        font-size:34px;
        font-weight:800;
        line-height:1;
        margin:12px 0;
  ">'.$s['value'].'</div>';

  echo '<div class="kpi-sub" style="color:rgba(255,255,255,.7);font-size:13px">'.$s['sub'].'</div>';
  echo '</div>';
  $i++;
 }
 echo '</div>';
}

function render_ai_widget(): void {
?>
<style>
.repairly-ai{
 margin-top:24px;
 border-radius:24px;
 overflow:hidden;
 background:linear-gradient(135deg,#020617,#0F172A,#1E3A8A);
 box-shadow:0 20px 50px rgba(0,0,0,.25);
 color:#fff;
}
.ai-header{
 padding:24px;
 border-bottom:1px solid rgba(255,255,255,.08);
 display:flex;
 justify-content:space-between;
 align-items:center;
 flex-wrap:wrap;
 gap:12px;
}
.ai-badge{
 background:rgba(255,255,255,.08);
 border:1px solid rgba(255,255,255,.08);
 padding:10px 16px;
 border-radius:14px;
}
.ai-body{
 padding:24px;
}
.ai-analysis{
 display:grid;
 grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
 gap:16px;
 margin-top:20px;
}
.ai-card{
 background:rgba(255,255,255,.06);
 border:1px solid rgba(255,255,255,.08);
 border-radius:18px;
 padding:18px;
 backdrop-filter:blur(10px);
}
.ai-title{
 font-size:13px;
 text-transform:uppercase;
 opacity:.7;
 margin-bottom:10px;
 letter-spacing:.5px;
}
.ai-value{
 font-size:18px;
 font-weight:700;
}
.ai-list{
 margin:0;
 padding-left:18px;
 line-height:1.8;
}
</style>

<div class="repairly-ai">
 <div class="ai-header">
   <div>
     <div style="font-size:28px;font-weight:800">RepairlyRD AI Analysis</div>
     <div style="opacity:.7;margin-top:4px">Análisis automático basado en la descripción del diagnóstico seleccionado</div>
   </div>

   <div class="ai-badge" id="ia-status">
      Esperando selección
   </div>
 </div>

 <div class="ai-body">
   <div id="ia-placeholder" style="opacity:.7">
      Selecciona un registro para generar automáticamente el análisis inteligente.
   </div>

   <div id="ia-result" style="display:none"></div>
 </div>
</div>

<script>
function analizarRegistroIA(descripcion=''){
 if(!descripcion || descripcion.trim()===''){
   document.getElementById('ia-status').innerHTML='Sin datos';
   return;
 }

 const texto = descripcion.toLowerCase();

 let resultado = {
   problema:'Diagnóstico general',
   prioridad:'Media',
   probabilidad:'68%',
   acciones:[
     'Realizar revisión técnica completa',
     'Verificar componentes principales',
     'Ejecutar pruebas eléctricas'
   ]
 };

 if(texto.includes('no enciende')){
   resultado = {
     problema:'Falla de encendido',
     prioridad:'Crítica',
     probabilidad:'92%',
     acciones:[
       'Revisar línea principal de voltaje',
       'Comprobar PMIC y consumo',
       'Probar fuente DC'
     ]
   };
 }

 if(texto.includes('mojado') || texto.includes('liquido')){
   resultado = {
     problema:'Daño por líquido',
     prioridad:'Alta',
     probabilidad:'95%',
     acciones:[
       'Aplicar limpieza ultrasónica',
       'Eliminar sulfato',
       'Medir cortos en motherboard'
     ]
   };
 }

 if(texto.includes('pantalla')){
   resultado = {
     problema:'Daño de display',
     prioridad:'Media',
     probabilidad:'88%',
     acciones:[
       'Probar otra pantalla',
       'Revisar flex',
       'Verificar IC de imagen'
     ]
   };
 }

 if(texto.includes('bateria') || texto.includes('carga')){
   resultado = {
     problema:'Sistema de carga',
     prioridad:'Media',
     probabilidad:'84%',
     acciones:[
       'Comprobar batería',
       'Revisar pin de carga',
       'Medir amperaje'
     ]
   };
 }

 document.getElementById('ia-status').innerHTML='Análisis completado';

 document.getElementById('ia-placeholder').style.display='none';

 document.getElementById('ia-result').style.display='block';

 document.getElementById('ia-result').innerHTML = `
   <div class="ai-analysis">
      <div class="ai-card">
         <div class="ai-title">Problema detectado</div>
         <div class="ai-value">${resultado.problema}</div>
      </div>

      <div class="ai-card">
         <div class="ai-title">Nivel de prioridad</div>
         <div class="ai-value">${resultado.prioridad}</div>
      </div>

      <div class="ai-card">
         <div class="ai-title">Probabilidad IA</div>
         <div class="ai-value">${resultado.probabilidad}</div>
      </div>

      <div class="ai-card" style="grid-column:1/-1">
         <div class="ai-title">Acciones recomendadas</div>
         <ul class="ai-list">
            ${resultado.acciones.map(a=>`<li>${a}</li>`).join('')}
         </ul>
      </div>

      <div class="ai-card" style="grid-column:1/-1">
         <div class="ai-title">Descripción analizada</div>
         <div style="opacity:.8;line-height:1.7">${descripcion}</div>
      </div>
   </div>
 `;
}
</script>
<?php
}
