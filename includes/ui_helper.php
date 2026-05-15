<?php
function render_stats_cards(array $stats): void {
 echo '<div class="kpi-grid" style="margin-bottom:18px;">';
 foreach($stats as $s){
  echo '<div class="kpi-card" style="background:'.$s['bg'].';--kpi-color:'.$s['color'].';">';
  echo '<div class="kpi-label" style="color:'.$s['color'].'"><i class="ti '.$s['icon'].'"></i> '.$s['label'].'</div>';
  echo '<div class="kpi-valor" style="color:#fff">'.$s['value'].'</div>';
  echo '<div class="kpi-sub">'.$s['sub'].'</div>';
  echo '</div>';
 }
 echo '</div>';
}
function render_ai_widget(): void {
?>
<style>
.repairly-ai{margin-top:18px;padding:22px;border-radius:24px;background:linear-gradient(135deg,#081b33,#123d67 60%,#1f7ae0);color:#fff;box-shadow:0 20px 40px rgba(0,0,0,.22)}
.repairly-ai textarea{width:100%;min-height:110px;border:none;border-radius:18px;padding:14px;background:rgba(255,255,255,.12);color:#fff;backdrop-filter:blur(10px)}
.repairly-ai textarea::placeholder{color:rgba(255,255,255,.7)}
.ai-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:16px}
.ai-box{background:rgba(255,255,255,.09);padding:14px;border-radius:18px;border:1px solid rgba(255,255,255,.1)}
</style>
<div class="repairly-ai">
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
<div><div style="font-size:24px;font-weight:800">RepairlyRD AI</div><div style="opacity:.75">Diagnóstico inteligente y recomendaciones técnicas</div></div>
<div id="ia-status" class="ai-box" style="padding:10px 14px">Lista para analizar</div></div>
<textarea id="ia-input" placeholder="Ej: iPhone 13 no enciende después de mojarse y se calienta cerca del conector de carga"></textarea>
<div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
<button type="button" onclick="analizarIA()" class="ordenes-ver-btn" style="background:#fff;color:#0c2340;border:none">Analizar equipo</button>
<button type="button" onclick="copiarResultadoIA()" class="ordenes-ver-btn">Copiar análisis</button>
</div>
<div id="ia-result" style="display:none"></div></div>
<script>
function analizarIA(){
 const texto=document.getElementById('ia-input').value.toLowerCase().trim();
 if(!texto){alert('Describe la falla del equipo');return;}
 const patrones=[
 {key:'pantalla',tipo:'Pantalla dañada',nivel:'Medio',sol:['Revisar flex','Probar display','Verificar touch IC']},
 {key:'bateria',tipo:'Problema de batería',nivel:'Bajo',sol:['Medir consumo','Probar batería nueva','Revisar pin de carga']},
 {key:'mojado',tipo:'Daño por líquido',nivel:'Alto',sol:['Limpieza ultrasónica','Eliminar sulfato','Medir líneas principales']},
 {key:'calienta',tipo:'Sobrecalentamiento',nivel:'Alto',sol:['Buscar corto','Cambiar pasta térmica','Revisar PMIC']},
 {key:'no enciende',tipo:'Falla de encendido',nivel:'Crítico',sol:['Medir voltajes','Revisar motherboard','Probar fuente DC']}
 ];
 let data={tipo:'Diagnóstico general',nivel:'Medio',sol:['Revisión técnica completa','Prueba de componentes','Verificar alimentación']};
 patrones.forEach(p=>{if(texto.includes(p.key)) data=p;});
 document.getElementById('ia-status').innerText='Analizando patrones...';
 setTimeout(()=>{
 document.getElementById('ia-status').innerText='Diagnóstico completado';
 document.getElementById('ia-result').style.display='block';
 document.getElementById('ia-result').innerHTML=`<div class="ai-grid"><div class="ai-box"><div style="opacity:.7;font-size:12px">TIPO</div><div style="font-size:22px;font-weight:700">${data.tipo}</div></div><div class="ai-box"><div style="opacity:.7;font-size:12px">PRIORIDAD</div><div style="font-size:22px;font-weight:700">${data.nivel}</div></div><div class="ai-box"><div style="opacity:.7;font-size:12px">PRECISIÓN IA</div><div style="font-size:22px;font-weight:700">94%</div></div></div><div class="ai-box" style="margin-top:16px"><div style="font-weight:700;margin-bottom:8px">Soluciones recomendadas</div>${data.sol.map(s=>`<div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,.08)">✔ ${s}</div>`).join('')}</div>`;
 },800)
}
function copiarResultadoIA(){navigator.clipboard.writeText(document.getElementById('ia-result').innerText)}
</script>
<?php }
