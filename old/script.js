function addEmail(){
  const input=document.getElementById('emailInput');
  if(!input.value) return;
  const span=document.createElement('span');
  span.innerText=input.value;
  document.getElementById('emailsList').appendChild(span);
  input.value='';
}