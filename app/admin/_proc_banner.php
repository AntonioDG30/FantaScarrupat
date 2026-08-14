<?php
/**
 * app/admin/_proc_banner.php — Banner "processo" mostrato all'invio dei form di import.
 *
 * Due comportamenti:
 *  • form[data-proc-chunked]  → driver REALE a blocchi: invia il file, poi chiede
 *    al server un blocco alla volta (fz_phase=step) mostrando l'avanzamento vero
 *    (file X/N, conteggi reali). Richieste brevi → niente timeout su Altervista.
 *  • form[data-proc]          → import sincrono: mostra spinner + le fasi (descrizione
 *    onesta, senza spunte finte), poi la pagina si ricarica con l'esito (flash).
 *
 * Estetica ispirata al modale di processo di PredictionSerieA. Spinner in puro CSS
 * (niente dipendenze da icone assenti); per le voci completate usa check_circle.
 * Includere una sola volta per pagina, prima di app_layout_end().
 */
?>
<div class="fsp-overlay" id="fspProc" aria-hidden="true">
  <div class="fsp-modal" role="dialog" aria-modal="true" aria-labelledby="fspTitle">
    <div class="fsp-head">
      <span class="fsp-ring" aria-hidden="true"></span>
      <div>
        <div class="fsp-head__title" id="fspTitle">Elaborazione in corso…</div>
        <div class="fsp-head__status" id="fspStatus">Inizializzazione…</div>
      </div>
    </div>
    <div class="fsp-track"><div class="fsp-fill" id="fspFill"></div></div>
    <div class="fsp-steps" id="fspLog"></div>
    <div class="fsp-foot">
      <button type="button" class="btn-sm btn-secondary" id="fspClose" style="display:none">Chiudi e aggiorna</button>
    </div>
  </div>
</div>

<style>
.fsp-overlay{position:fixed;inset:0;z-index:9000;display:none;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.6);backdrop-filter:blur(5px)}
.fsp-overlay.is-open{display:flex}
.fsp-modal{width:560px;max-width:100%;background:var(--c-bg,#fff);color:var(--c-text,#111);border:1px solid var(--c-border,#ddd);border-radius:var(--r-lg,14px);padding:22px;box-shadow:0 30px 80px rgba(0,0,0,.5)}
.fsp-head{display:flex;align-items:center;gap:13px;margin-bottom:18px}
.fsp-head__title{font-size:15px;font-weight:700;line-height:1.3}
.fsp-head__status{font-size:12px;color:var(--c-text-muted,#666);margin-top:3px;font-style:italic;line-height:1.4}
.fsp-track{height:6px;background:rgba(128,128,128,.18);border-radius:6px;overflow:hidden;margin-bottom:12px}
.fsp-fill{height:100%;width:4%;background:var(--c-accent,#4f46e5);border-radius:6px;transition:width .45s ease}
.fsp-fill--done{background:var(--c-success,#0a7d28)}
.fsp-steps{display:flex;flex-direction:column;gap:3px;max-height:250px;overflow-y:auto}
.fsp-step{display:flex;align-items:center;gap:9px;padding:8px 11px;border-radius:8px;background:rgba(128,128,128,.08);border:1px solid transparent;font-size:12.5px;color:var(--c-text-muted,#555);line-height:1.45;animation:fsp-in .16s ease both}
@keyframes fsp-in{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
.fsp-step.is-ok{background:rgba(10,125,40,.1);border-color:rgba(10,125,40,.18);color:var(--c-text,#111)}
.fsp-step.is-err{background:rgba(220,53,69,.1);border-color:rgba(220,53,69,.2);color:var(--c-text,#111)}
.fsp-step__ic-wrap{display:flex;align-items:center;justify-content:center;width:18px;flex-shrink:0}
.fsp-step__ic{font-size:17px;color:var(--c-success,#0a7d28)}
.fsp-step__ic--err{color:var(--c-danger,#dc3545)}
.fsp-dot{width:6px;height:6px;border-radius:50%;background:rgba(128,128,128,.55)}
.fsp-ring{display:inline-block;width:24px;height:24px;border:2.6px solid rgba(128,128,128,.25);border-top-color:var(--c-accent,#4f46e5);border-radius:50%;animation:fsp-spin .8s linear infinite;flex-shrink:0}
.fsp-ring--sm{width:15px;height:15px;border-width:2px}
@keyframes fsp-spin{to{transform:rotate(360deg)}}
.fsp-foot{display:flex;justify-content:flex-end;margin-top:16px}
</style>

<script>
(function(){
  'use strict';
  var ov=document.getElementById('fspProc');
  if(!ov) return;
  var elTitle=document.getElementById('fspTitle'), elStatus=document.getElementById('fspStatus'),
      elFill=document.getElementById('fspFill'), elLog=document.getElementById('fspLog'),
      elClose=document.getElementById('fspClose');
  function esc(s){return String(s==null?'':s).replace(/[&<>"]/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];});}

  function reset(title){
    elTitle.textContent=title||'Elaborazione in corso…'; elStatus.textContent='Inizializzazione…';
    elLog.innerHTML=''; elFill.style.width='4%'; elFill.classList.remove('fsp-fill--done');
    elClose.style.display='none';
    ov.classList.add('is-open'); ov.setAttribute('aria-hidden','false');
  }
  function icon(kind){
    if(kind==='ok')  return '<span class="material-symbols-rounded fsp-step__ic">check_circle</span>';
    if(kind==='err') return '<span class="material-symbols-rounded fsp-step__ic fsp-step__ic--err">error</span>';
    if(kind==='run') return '<span class="fsp-ring fsp-ring--sm"></span>';
    return '<span class="fsp-dot"></span>';
  }
  function addRow(text, kind){
    var r=document.createElement('div');
    r.className='fsp-step'+(kind==='ok'?' is-ok':(kind==='err'?' is-err':''));
    r.innerHTML='<span class="fsp-step__ic-wrap">'+icon(kind)+'</span><span class="fsp-step__txt">'+esc(text)+'</span>';
    elLog.appendChild(r); elLog.scrollTop=elLog.scrollHeight; return r;
  }
  function setRow(r, text, kind){
    if(!r) return;
    r.className='fsp-step'+(kind==='ok'?' is-ok':(kind==='err'?' is-err':''));
    r.querySelector('.fsp-step__ic-wrap').innerHTML=icon(kind);
    if(text!=null) r.querySelector('.fsp-step__txt').textContent=text;
  }
  function fail(e){ elStatus.textContent='Operazione interrotta.'; addRow((e&&e.message)||'Errore di rete.','err'); elClose.style.display=''; }

  /* ── Driver REALE a blocchi (form[data-proc-chunked]) ─────────────── */
  function runChunked(form){
    reset(form.getAttribute('data-proc-title')||'Importazione…');
    var url=form.getAttribute('action');
    var csrf=(form.querySelector('[name="csrf_token"]')||{}).value||'';
    elStatus.textContent='Caricamento del file…';
    var row=addRow('Caricamento e apertura del file…','run');

    var fd=new FormData(form); fd.set('fz_phase','start');
    fetch(url,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      if(!d.ok) throw new Error(d.message||'Avvio non riuscito.');
      setRow(row, d.message, 'ok');
      var total=d.total||0;
      if(total===0){ elStatus.textContent='Niente da importare.'; elClose.style.display=''; return; }
      (function step(){
        var rr=addRow('Elaborazione blocco…','run');
        var f2=new FormData(); f2.set('fz_phase','step'); f2.set('csrf_token',csrf);
        fetch(url,{method:'POST',body:f2}).then(function(r){return r.json();}).then(function(s){
          if(!s.ok) throw new Error(s.message||'Errore.');
          setRow(rr, 'File '+s.processed+'/'+s.total+' · '+s.message, 'ok');
          elStatus.textContent='Elaborati '+s.processed+' di '+s.total+' file';
          elFill.style.width=(s.total? Math.round(s.processed/s.total*100):100)+'%';
          if(s.done){
            elFill.style.width='100%'; elFill.classList.add('fsp-fill--done');
            elStatus.textContent='Completato.';
            addRow(s.final_message||'Completato.','ok');
            elClose.style.display='';
          } else { step(); }
        }).catch(fail);
      })();
    }).catch(fail);
  }

  /* ── Driver FORM UNICO: calendario (AJAX) → formazioni (a blocchi) → calcolo ── */
  function runUnified(form){
    reset(form.getAttribute('data-proc-title')||'Importazione…');
    var calUrl=form.getAttribute('data-cal-url'), formUrl=form.getAttribute('data-form-url');
    var get=function(n){ return form.querySelector('[name="'+n+'"]'); };
    var csrf=(get('csrf_token')||{}).value||'', idcd=(get('id_cd')||{}).value||'';
    var inclEl=get('incl_formazioni'), formEl=get('file_formazioni');
    var hasForm=!!(inclEl && inclEl.checked && formEl && formEl.files && formEl.files.length);
    var calSovr=get('sovrascrivi_cal'), formSovr=get('sovrascrivi_form');
    var cal1=get('file_calendario'), cal2=get('file_calendario2');

    elStatus.textContent='Importazione del calendario…';
    var rowCal=addRow('Importazione del calendario…','run');
    var fd=new FormData();
    fd.set('ajax','1'); fd.set('csrf_token',csrf); fd.set('id_cd',idcd);
    fd.set('calc_vincitore', hasForm ? '' : '1'); // con formazioni il calcolo lo fa l'import formazioni
    if(calSovr && calSovr.checked) fd.set('sovrascrivi','1');
    if(cal1 && cal1.files[0]) fd.set('file_calendario', cal1.files[0]);
    if(cal2 && cal2.files[0]) fd.set('file_calendario2', cal2.files[0]);

    fetch(calUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      if(!d.ok) throw new Error(d.message||'Import calendario non riuscito.');
      setRow(rowCal, d.message, 'ok'); elFill.style.width='45%';
      if(!hasForm){
        elFill.style.width='100%'; elFill.classList.add('fsp-fill--done');
        elStatus.textContent='Completato.'; addRow('Competizione importata.','ok'); elClose.style.display=''; return;
      }
      elStatus.textContent='Caricamento delle formazioni…';
      var rowF=addRow('Caricamento e apertura dello ZIP formazioni…','run');
      var fs=new FormData();
      fs.set('fz_phase','start'); fs.set('csrf_token',csrf); fs.set('id_cd',idcd);
      if(formSovr && formSovr.checked) fs.set('sovrascrivi','1');
      fs.set('file_formazioni', formEl.files[0]);
      fetch(formUrl,{method:'POST',body:fs}).then(function(r){return r.json();}).then(function(s){
        if(!s.ok) throw new Error(s.message||'Avvio formazioni non riuscito.');
        setRow(rowF, s.message, 'ok');
        if((s.total||0)===0){ elFill.style.width='100%'; elFill.classList.add('fsp-fill--done'); elStatus.textContent='Completato.'; elClose.style.display=''; return; }
        (function step(){
          var rr=addRow('Elaborazione blocco…','run');
          var f2=new FormData(); f2.set('fz_phase','step'); f2.set('csrf_token',csrf);
          fetch(formUrl,{method:'POST',body:f2}).then(function(r){return r.json();}).then(function(st){
            if(!st.ok) throw new Error(st.message||'Errore.');
            setRow(rr,'File '+st.processed+'/'+st.total+' · '+st.message,'ok');
            elStatus.textContent='Elaborati '+st.processed+' di '+st.total+' file';
            elFill.style.width=(45+(st.total? Math.round(st.processed/st.total*55):55))+'%';
            if(st.done){ elFill.style.width='100%'; elFill.classList.add('fsp-fill--done'); elStatus.textContent='Completato.'; addRow(st.final_message||'Completato.','ok'); elClose.style.display=''; }
            else { step(); }
          }).catch(fail);
        })();
      }).catch(fail);
    }).catch(fail);
  }

  /* ── Import sincrono onesto (form[data-proc]) ─────────────────────── */
  function showPlain(form){
    reset(form.getAttribute('data-proc-title')||'Elaborazione in corso…');
    elStatus.textContent='Elaborazione in corso… non chiudere la pagina.';
    elFill.style.width='30%';
    (form.getAttribute('data-proc-steps')||'').split('|').map(function(s){return s.trim();})
      .filter(Boolean).forEach(function(s){ addRow(s, 'dot'); });
  }

  document.addEventListener('submit', function(e){
    var fu=e.target.closest('form[data-proc-unified]');
    if(fu){ e.preventDefault(); runUnified(fu); return; }
    var fc=e.target.closest('form[data-proc-chunked]');
    if(fc){ e.preventDefault(); runChunked(fc); return; }
    var fp=e.target.closest('form[data-proc]');
    if(fp){ showPlain(fp); } // niente preventDefault: invia normalmente
  }, false);

  // "Chiudi e aggiorna": ricarica per riflettere i dati importati
  elClose.addEventListener('click', function(){ window.location.reload(); });
})();
</script>

<script>
/* Enhancement input file .adm-file: nome del file selezionato + drag & drop */
(function(){
  document.querySelectorAll('.adm-file').forEach(function(box){
    var input = box.querySelector('.adm-file__input'),
        text  = box.querySelector('.adm-file__text');
    if(!input || !text) return;
    var def = text.textContent;
    input.addEventListener('change', function(){
      if(input.files && input.files.length){
        text.textContent = input.files.length > 1 ? (input.files.length + ' file selezionati') : input.files[0].name;
        box.classList.add('has-file');
      } else { text.textContent = def; box.classList.remove('has-file'); }
    });
    ['dragenter','dragover'].forEach(function(ev){ box.addEventListener(ev, function(e){ e.preventDefault(); box.classList.add('is-drag'); }); });
    ['dragleave','dragend','drop'].forEach(function(ev){ box.addEventListener(ev, function(){ box.classList.remove('is-drag'); }); });
  });
})();
</script>
