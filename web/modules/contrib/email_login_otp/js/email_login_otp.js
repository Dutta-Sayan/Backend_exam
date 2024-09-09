setTimeout(()=>{
  startTimer();
}, 1000);

function startTimer() {
  var presentTime = document.getElementById('time').innerHTML;
  var timeArray = presentTime.split(/[:]+/);
  var m = timeArray[0];
  var s = checkSecond((timeArray[1] - 1));
  if(s==59){m=m-1}
  if(m<0){
    return
  }

  document.getElementById('time').innerHTML = m + ":" + s;
  if (m == 0 && s == 00) {
    if(document.getElementById('replace') != null) {
      var link_txt = document.getElementById('replace').innerHTML;
      document.getElementById('replace').innerHTML = "<a href='javascript:void(0)' id='resend'>"+link_txt+"</a>";
      document.getElementById('replace').style.display = 'inline-block';
      document.getElementById('resend').addEventListener('click', function(){
        window.location = window.location.href+'/resend';
      })
    }
    if (document.querySelector('input[name="send-otp"]') != null) {
      document.querySelector('input[name="send-otp"]').style.display = "block";
    }
  }
  setTimeout(startTimer, 1000);

}

function checkSecond(sec) {
  if (sec < 10 && sec >= 0) {sec = "0" + sec}; // add zero in front of numbers < 10
  if (sec < 0) {sec = "59"};
  return sec;
}
