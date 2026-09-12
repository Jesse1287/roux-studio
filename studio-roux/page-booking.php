<?php
/**
 * Template Name: Booking Form
 */
get_header();

if (isset($_GET['booking']) && $_GET['booking'] === 'sent') : ?>
<main class="page-content">
  <div class="container container-narrow" style="padding:120px 24px;text-align:center;">
    <div class="glass">
      <h2>Booking Request Sent</h2>
      <p style="color:var(--text-muted);margin:20px 0;">Thank you! We've received your booking request and will review it within 24 hours. You'll receive a confirmation email shortly.</p>
      <a href="<?php echo home_url('/booking/'); ?>" class="btn">Submit Another</a>
    </div>
  </div>
</main>
<?php else : ?>
<main class="page-content">
  <div class="container container-narrow">
    <div class="section-title">
      <h2>Book a Session</h2>
      <p>Select your service, choose a date and time, and we'll get back to you within 24 hours.</p>
    </div>

    <?php if (isset($_GET['booking']) && $_GET['booking'] === 'error') : ?>
      <div class="alert alert-error">There was an error submitting your booking. Please try again.</div>
    <?php endif; ?>

    <div class="glass">
      <form method="post" action="">
        <?php wp_nonce_field('studio_booking_submit', '_booking_nonce'); ?>
        <input type="hidden" name="studio_booking" value="1">

        <div class="form-group">
          <label for="name">Full Name *</label>
          <input type="text" id="name" name="name" required placeholder="Your name">
        </div>

        <div class="form-group">
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" required placeholder="you@example.com">
        </div>

        <div class="form-group">
          <label for="phone">Phone</label>
          <input type="tel" id="phone" name="phone" placeholder="(555) 555-5555">
        </div>

        <div class="form-group">
          <label for="service">Service *</label>
          <select id="service" name="service" required>
            <option value="">Select a service...</option>
            <option value="Tier 1 - Recording ($75/hr)" data-hourly="true">Tier 1 - Recording ($75/hr)</option>
            <option value="Tier 2 - Mixing ($150)">Tier 2 - Mixing ($150)</option>
            <option value="Tier 3 - Mixing + Mastering ($200)">Tier 3 - Mixing + Mastering ($200)</option>
            <option value="Tier 4 - Full Production ($250)">Tier 4 - Full Production ($250)</option>
            <option value="FOH Engineer ($50/hr)" data-hourly="true">FOH Engineer - Live Sound ($50/hr)</option>
            <option value="Consultation (Free)">Consultation (Free)</option>
          </select>
        </div>

        <div class="form-group">
          <label>Sessions (Date + Time)</label>
          <div id="slot-container">
            <div class="slot-row">
              <div class="slot-field">
                <label for="slot_date_0">Date</label>
                <input type="date" id="slot_date_0" name="slot_date[]" required>
              </div>
              <div class="slot-field">
                <label for="slot_start_0">Start Time</label>
                <input type="time" id="slot_start_0" name="slot_start[]" required>
              </div>
              <div class="slot-field">
                <label for="slot_end_0">End Time</label>
                <input type="time" id="slot_end_0" name="slot_end[]" required>
              </div>
            </div>
          </div>
          <button type="button" class="btn btn-sm" id="add-slot-btn" style="margin-top:8px;">+ Add Another Session</button>
        </div>

        <div id="estimate-box" style="display:none;margin:16px 0;padding:16px;border:1px solid var(--gold);border-radius:8px;background:rgba(212,165,116,0.05);">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="color:var(--text-muted);font-size:0.9rem;">Estimated Total:</span>
            <strong id="estimate-amount" style="font-size:1.4rem;color:var(--gold);">$0.00</strong>
          </div>
          <p id="estimate-note" style="margin:8px 0 0;font-size:0.75rem;color:var(--text-muted);">Final amount confirmed after booking review.</p>
        </div>

        <div class="form-group">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" placeholder="Studio or venue address">
        </div>

        <div class="form-group">
          <label for="notes">Additional Notes</label>
          <textarea id="notes" name="notes" rows="4" placeholder="Tell us about your project..."></textarea>
        </div>

        <div class="cf-turnstile" style="margin-bottom:24px;" data-sitekey="<?php echo defined('STUDIO_TURNSTILE_SITEKEY') ? esc_attr(STUDIO_TURNSTILE_SITEKEY) : ''; ?>"></div>

        <button type="submit" class="btn btn-primary btn-block" style="padding:16px;">Submit Booking Request</button>
      </form>
    </div>
  </div>
</main>
<script>
(function(){
  var rates = {
    "Tier 1": {rate:75,hourly:true},
    "Tier 2": {rate:150,hourly:false},
    "Tier 3": {rate:200,hourly:false},
    "Tier 4": {rate:250,hourly:false},
    "FOH Engineer": {rate:50,hourly:true},
    "Consultation": {rate:0,hourly:false}
  };
  function getRate(svc){
    if(!svc) return null;
    for(var k in rates){ if(svc.indexOf(k)!==-1) return rates[k]; }
    return {rate:75,hourly:true};
  }
  function calcHours(s,e){
    if(!s||!e) return 0;
    var sp=s.split(":"),ep=e.split(":");
    var diff=(parseInt(ep[0])*60+parseInt(ep[1]))-(parseInt(sp[0])*60+parseInt(sp[1]));
    return diff>0?Math.round(diff/60*4)/4:0;
  }
  function updateEstimate(){
    var svc=document.getElementById("service");
    var info=getRate(svc?svc.value:"");
    var estBox=document.getElementById("estimate-box");
    var estAmt=document.getElementById("estimate-amount");
    var estNote=document.getElementById("estimate-note");
    if(!info||!estBox||!estAmt){return;}
    if(info.rate===0){estBox.style.display="none";return;}
    estBox.style.display="block";
    if(info.hourly){
      var totalH=0;
      var rows=document.querySelectorAll(".slot-row");
      for(var i=0;i<rows.length;i++){
        var st=rows[i].querySelector("input[name*=slot_start]");
        var en=rows[i].querySelector("input[name*=slot_end]");
        totalH+=calcHours(st?st.value:"",en?en.value:"");
      }
      if(totalH<=0){estAmt.textContent="$"+info.rate+"/hr";if(estNote)estNote.textContent="Select dates & times to see your total.";return;}
      estAmt.textContent="$"+(info.rate*totalH).toFixed(2)+" ("+totalH+" hrs \u00d7 $"+info.rate+"/hr)";
      if(estNote)estNote.textContent="Final amount confirmed after booking review.";
    }else{
      estAmt.textContent="$"+info.rate.toFixed(2);
      if(estNote)estNote.textContent="Flat rate \u2014 final amount confirmed after booking review.";
    }
  }
  var svc=document.getElementById("service");
  if(svc) svc.addEventListener("change",updateEstimate);
  var addBtn=document.getElementById("add-slot-btn");
  if(addBtn) addBtn.addEventListener("click",function(){
    var c=document.getElementById("slot-container");
    var r=document.createElement("div");
    r.className="slot-row";
    r.innerHTML='<div class="slot-field"><label>Date</label><input type="date" name="slot_date[]" required></div><div class="slot-field"><label>Start Time</label><input type="time" name="slot_start[]" required></div><div class="slot-field"><label>End Time</label><input type="time" name="slot_end[]" required></div>';
    c.appendChild(r);
    r.querySelectorAll("input").forEach(function(inp){inp.addEventListener("change",updateEstimate);});
  });
  document.querySelectorAll(".slot-row input").forEach(function(inp){inp.addEventListener("change",updateEstimate);});
  updateEstimate();
  var form=document.querySelector("form");
  if(form) form.addEventListener("submit",function(e){
    var slots=document.querySelectorAll(".slot-row");
    for(var i=0;i<slots.length;i++){
      var st=slots[i].querySelector("input[name*=slot_start]");
      var en=slots[i].querySelector("input[name*=slot_end]");
      if(st&&en&&st.value&&en.value&&en.value<=st.value){
        e.preventDefault();
        alert("End time must be after start time.");
        return;
      }
    }
  });
})();
</script>
<?php endif; ?>
<?php get_footer(); ?>
