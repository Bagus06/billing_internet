-- Parameter provisioning PPP Profile untuk setiap paket internet.

ALTER TABLE internet_packages
    ADD COLUMN ppp_local_address VARCHAR(100) NULL AFTER ppp_profile_name,
    ADD COLUMN ppp_remote_address VARCHAR(100) NULL AFTER ppp_local_address,
    ADD COLUMN ppp_rate_limit VARCHAR(100) NULL AFTER ppp_remote_address,
    ADD COLUMN ppp_dns_server VARCHAR(150) NULL AFTER ppp_rate_limit,
    ADD COLUMN ppp_only_one VARCHAR(10) NOT NULL DEFAULT 'yes' AFTER ppp_dns_server,
    ADD COLUMN ppp_change_tcp_mss VARCHAR(10) NOT NULL DEFAULT 'yes' AFTER ppp_only_one;

