<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Presensi DSCM Kids</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
html,body{
    height:100%;
    margin:0;
}
body{
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(135deg,#ff5fa2,#9b5cff);
    overflow:hidden;
}
#logo{
    width:180px;
    cursor:pointer;
    transition:all .6s ease;
}
#logo.animate{
    transform:scale(15);
    opacity:0;
}
</style>
</head>
<body>

<img src="/assets/home/logo.png" id="logo">

<script>
document.getElementById('logo').addEventListener('click',function(){
    this.classList.add('animate');
    setTimeout(()=>{ window.location.href='/login'; },600);
});
</script>

</body>
</html>
