import React, { useState, useEffect } from "react";
import {
    Modal,
    Spin,
    Card,
    AutoComplete,
    Row,
    Col,
    notification,
    Table,
    Button,
} from "antd";
import axios from "axios";

export default function Index({ pasien }) {
    const [fetchMrDiagnosaLoading, setFetchMrDiagnosaLoading] = useState(false);
    const [dataMrDiagnosa, setDataMrDiagnosa] = useState(null);

    // Fungsi untuk mengambil data diagnosa dari tabbel MR_DIAGNOSA
    const fetchMRDiagnosa = () => {
        setFetchMrDiagnosaLoading(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_mr_diagnosa", {
                    kode_reg: pasien.FRPNOTRANSAKSIKJ,
                })
            )
            .then((response) => {
                setDataMrDiagnosa(response.data.data);
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setFetchMrDiagnosaLoading(false);
            });
        return;
    };

    useEffect(() => {
        fetchMRDiagnosa();
    }, []);

    return (
        <Card loading={fetchMrDiagnosaLoading}>
            <p>
                <strong>Amnanese:</strong>
            </p>
            <p>{dataMrDiagnosa?.MRDDIAGNOSA_UTAMA}</p>
            <p>
                <strong>Catatan Khusus:</strong>
            </p>
            <p>{dataMrDiagnosa?.MRCATATANKHUSUS}</p>
        </Card>
    );
}
