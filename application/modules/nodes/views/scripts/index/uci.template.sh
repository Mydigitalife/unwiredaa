uci batch << EOF
set dropbear.@dropbear[0]=dropbear
set dropbear.@dropbear[0].PasswordAuth=on
set dropbear.@dropbear[0].Port=22
set network.loopback=interface
set network.loopback.ifname=lo
set network.loopback.proto=static
set network.loopback.ipaddr=127.0.0.1
set network.loopback.netmask=255.0.0.0
set network.lan=interface
set network.lan.type=bridge
set network.lan.ifname="wlan0 tap0"
delete network.wan
set network.wan=interface
set network.wan.ifname=eth0
<?php if ($this->node->getSettings()->getDhcp()) : ?>
set network.wan.proto=dhcp
<?php else : ?>
set network.wan.proto=static
set network.wan.ipaddr=<?php echo $this->node->getSettings()->getIpaddress() . "\n"; ?>
set network.wan.netmask=<?php echo $this->node->getSettings()->getNetmask() . "\n"; ?>
set network.wan.gateway=<?php echo $this->node->getSettings()->getGateway() . "\n"; ?>
set network.wan.dns="<?php echo $this->node->getSettings()->getDnsservers() . "\"\n"; ?>
<?php endif; // dhcp ?>
delete openvpn.custom_config
delete openvpn.sample_server
delete openvpn.sample_client
set openvpn.management=openvpn
set openvpn.management.enable=1
set openvpn.management.client=1
set openvpn.management.dev=tun
set openvpn.management.proto=udp
set openvpn.management.remote=vpn.wlan.skiamade.com
set openvpn.management.port=1195
set openvpn.management.resolv_retry=infinite
set openvpn.management.nobind=1
set openvpn.management.persist_key=1
set openvpn.management.persist_tun=1
set openvpn.management.ca=/etc/openvpn/ca.crt
set openvpn.management.cert=/etc/openvpn/client.crt
set openvpn.management.key=/etc/openvpn/client.key
set openvpn.management.comp_lzo=1
set openvpn.management.verb=3
set openvpn.management.keepalive="10 60"
set openvpn.clients=openvpn
set openvpn.clients.enable=1
set openvpn.clients.client=1
set openvpn.clients.dev=tap
set openvpn.clients.proto=udp
set openvpn.clients.remote=vpn.wlan.skiamade.com
set openvpn.clients.port=1194
set openvpn.clients.resolv_retry=infinite
set openvpn.clients.nobind=1
set openvpn.clients.persist_key=1
set openvpn.clients.persist_tun=1
set openvpn.clients.ca=/etc/openvpn/ca.crt
set openvpn.clients.cert=/etc/openvpn/client.crt
set openvpn.clients.key=/etc/openvpn/client.key
set openvpn.clients.comp_lzo=1
set openvpn.clients.verb=3
set openvpn.clients.keepalive="10 60"
set system.@system[0]=system
set system.@system[0].hostname="<?php echo str_replace(array(':','-'), '', $this->node->getMac()) . "\"\n"; ?> 
set system.@system[0].zonename=Europe/Vienna
set system.@system[0].timezone=CET-1CEST,M3.5.0,M10.5.0/3
set system.@system[0].cronloglevel=9
set system.@button[0]=button
set system.@button[0].button=reset
set system.@button[0].action=released
set system.@button[0].handler="logger reboot"
set system.@button[0].min=0
set system.@button[0].max=4
set system.@button[1]=button
set system.@button[1].button=reset
set system.@button[1].action=released
set system.@button[1].handler="logger factory default"
set system.@button[1].min=5
set system.@button[1].max=30
set wireless.radio0=wifi-device
<?php
    if ($this->node->getSettings()->getWifiEnabled()) :
?>
set wireless.radio0.disabled=0
<?php
    else:
?>
set wireless.radio0.disabled=1
<?php
    endif;
?>
set wireless.radio0.type=mac80211
set wireless.radio0.channel=<?php echo $this->node->getSettings()->getChannel() . "\n"; ?>
set wireless.radio0.macaddr=<?php echo $this->node->getMac() . "\n"; ?>
set wireless.radio0.hwmode=11g
set wireless.@wifi-iface[0]=wifi-iface
set wireless.@wifi-iface[0].device=radio0
set wireless.@wifi-iface[0].network=lan
set wireless.@wifi-iface[0].mode=ap
set wireless.@wifi-iface[0].ssid="<?php echo $this->node->getSettings()->getSsid() . "\"\n"; ?>
set wireless.@wifi-iface[0].isolate=1
set wireless.@wifi-iface[0].encryption=none
set wireless.@wifi-iface[0].acct_server=172.31.0.1
set wireless.@wifi-iface[0].acct_port=1645
set wireless.@wifi-iface[0].acct_secret=titss4hostapd
set wireless.@wifi-iface[0].nasid=<?php echo dechex($this->node->getNodeId()) . "\n"; ?> 
set crontabs.@crontab[0].minutes=<?php echo ($this->node->getNodeId() % 60) . "\n"; ?>
<?php
    if ($this->node->getSettings()->getActivefrom() > 0 && $this->node->getSettings()->getActiveto() > 0) :
?>
set crontabs.@crontab[1].hours=<?php echo (int) $this->node->getSettings()->getActivefrom(); ?>

set crontabs.@crontab[1].enabled=1
set crontabs.@crontab[2].hours=<?php echo (int) $this->node->getSettings()->getActiveto(); ?>

set crontabs.@crontab[2].enabled=1
<?php
    else :
?>
set crontabs.@crontab[1].hours=*
set crontabs.@crontab[1].enabled=0
set crontabs.@crontab[2].hours=*
set crontabs.@crontab[2].enabled=0
<?php
    endif;

    $trafficLimit = (int) $this->node->getSettings()->getTrafficlimit();    
?>
set custom.limits=traffic
set custom.limits.traffic=<?php echo $trafficLimit; ?>

EOF
