UPDATE `tplugineinstellungen` conf JOIN `tplugin` plug ON conf.kPlugin = plug.kPlugin SET conf.cWert='1' WHERE conf.cWert='on';
