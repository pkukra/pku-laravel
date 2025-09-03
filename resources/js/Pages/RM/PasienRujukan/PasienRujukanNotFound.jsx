import React, { useState, useEffect } from "react";
import { Card } from "antd";
import axios from "axios";

export default function Index({ pasien }) {
    const storeNotFoundData = () => {
        const kode_reg_kj = pasien?.FRPNOTRANSAKSIKJ || "0";
        axios
            .post(route("rm.pasien-rujukan.store_not_found"), {
                kode_reg_kj: kode_reg_kj,
            })
            .then((response) => {
                console.log("rm.pasien-rujukan.store_not_found");
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
            <Card>Pasien rajal tidak ditemukan</Card>
        </>
    );
}
