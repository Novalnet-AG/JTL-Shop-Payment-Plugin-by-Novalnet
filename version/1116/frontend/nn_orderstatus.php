<?php

Shop::DB()->query('UPDATE tbestellung SET cAbgeholt = "Y" WHERE kBestellung="' . $args_arr['oBestellung']->kBestellung . '"', 4);
