import React, { useState, useEffect } from "react";
import { Card } from "antd";
import axios from "axios";

export default function Index({ pasien }) {
    const urls = window.location.pathname;
    const storeNotFoundData = () => {
        const kode_reg = pasien?.PRWINO_TRANSAKSI || "0";
        axios
            .post(route("rm.pasien-rujukan.store_not_found"), {
                kode_reg: kode_reg,
                urls: urls,
            })
            .then((response) => {
                console.log("rm.pasien-ranap.store_not_found");
            })
            .catch((error) => {
                console.error("Error store_not_found:", error);
            });
    };

    useEffect(() => {
        storeNotFoundData();
    }, [pasien]);
    return (
        <>
            <Card>Pasien ranap tidak ditemukan</Card>
        </>
    );
}
