import React, { useState, useEffect } from "react";
import { Modal, Card, Button, Tooltip, notification, Spin } from "antd";
import axios from "axios";

export default function Index({ pasien, user, noSep, setNoSep }) {
    const [loadingSep, setLoadingSep] = useState(false);
    // Fetch diagnosa with lazy loading support
    const fetchNoSep = async () => {
        setLoadingSep(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_nomer_sep", {
                    kode_reg: pasien.FRPNOTRANSAKSI,
                    kode_reg_kj: pasien.FRPNOTRANSAKSIKJ,
                })
            )
            .then((response) => {
                setNoSep(response?.data?.data?.FMNOSEP || null);
                console.log(response?.data?.data?.FMNOSEP);
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingSep(false);
            });
    };

    useEffect(() => {
        fetchNoSep();
    }, []);

    return (
        <>
            <Card
                title={"Nomer SEP"}
                loading={loadingSep}
                style={{ margin: 5 }}
            >
                {noSep}
            </Card>
        </>
    );
}
