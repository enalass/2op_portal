<?PHP
  if(($this->session->userdata('logged'))==TRUE) {
    if(($this->session->userdata('acceso'))>=100){
?>
	
<?PHP
    }else{
      ?>
        <section>
          <h2>ACCESS DENIED, CONTACT YOUR ADMINISTRATOR</h2>
        </section>
      <?PHP
    }
  }
?>