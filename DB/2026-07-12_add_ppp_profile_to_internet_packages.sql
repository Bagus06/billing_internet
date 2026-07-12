-- Relasi paket internet ke PPP Profile MikroTik.

ALTER TABLE internet_packages
    ADD COLUMN router_id INT UNSIGNED NULL AFTER package_name,
    ADD COLUMN ppp_profile_key VARCHAR(100) NULL AFTER router_id,
    ADD COLUMN ppp_profile_name VARCHAR(150) NULL AFTER ppp_profile_key,
    ADD KEY idx_internet_packages_router_profile (router_id, ppp_profile_key);

