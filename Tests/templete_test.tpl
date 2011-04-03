<!-- year = 30 -->
aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa

<?php if($this->year > 18) { ?>major<?php } ?>
</br>
<?php if($this->year > 18) { ?>major<?php } else { ?>not major<?php } ?>
</br>
<?php if($this->year < 20) { ?>less than 20 years<?php } elseif ($this->year < 30) { ?>less than 30 years<?php } ?>
</br>
<?php foreach ($this->tati as $this->mami) { ?>
do $this->mami;
<?php } ?>;
<!-- isLogged() return true if user is logged -->
<?php if(isLogged()) { ?>Hello $this->name<?php } else { ?>Not Logged<?php } ?>