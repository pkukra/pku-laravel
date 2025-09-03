import React, { useState, useEffect } from "react";
import { Card } from "antd";
import axios from "axios";

export default function Index({ pasien }) {
    const urls = window.location.pathname;
    const storeNotFoundData = () => {
        const kode_reg = pasien?.FRPNOTRANSAKSIKJ || "0";
        axios
            .post(route("rm.pasien-rujukan.store_not_found"), {
                kode_reg: kode_reg,
                urls: urls,
            })
            .then((response) => {
                console.log("rm.pasien-rujukan.store_not_found ", kode_reg);
            })
            .catch((error) => {
                console.error("Error store_not_found:", error);
            });
    };

    useEffect(() => {
        console.log(urls);
        storeNotFoundData();
    }, [pasien]);
    return (
        <>
            <Card>Pasien rajal tidak ditemukan</Card>
        </>
    );
}
