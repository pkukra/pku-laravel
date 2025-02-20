import React, { useState } from "react";
import { Head } from "@inertiajs/react";
import { Col, Row, Card } from "antd";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PasienRujukanDetailProfile from "./PasienRujukanDetailProfile";
import PasienRujukanDetailDiagnosaList from "./PasienRujukanDetailDiagnosaList";
import PasienRujukanDetailProcedureList from "./PasienRujukanDetailProcedureList";
import PasienRujukanDetailAmnanesaCatatan from "./PasienRujukanDetailAmnanesaCatatan";
import PasienRujukanDetailSEP from "./PasienRujukanDetailSEP";
import PasienRujukanDetailResume from "./PasienRujukanDetailResume";
import PasienRujukanDetailHasilLab from "./PasienRujukanDetailHasilLab";
import PasienRujukanDetailCaraMasukPulang from "./PasienRujukanDetailCaraMasukPulang";

export default function PasienRujukanDetail({
    auth,
    pasien: initialPasien,
    kode_reg,
}) {
    const [pasien, setPasien] = useState(initialPasien);
    const [pasienLoading, setPasienLoading] = useState(false);

    const reFetchPasien = () => {
        setPasienLoading(true);
        axios
            .get(
                route("rm.pasien-rujukan.detail_data", {
                    kode_reg: kode_reg,
                })
            )
            .then((response) => {
                console.log(response?.data);
                setPasien(response?.data?.pasien);
            })
            .catch((error) => {
                console.error("Error fetching data pasien:", error);
            })
            .finally(() => {
                setPasienLoading(false);
            });
        return;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <p className="font-semibold text-lg text-gray-800 leading-tight">
                    Detail Kunjungan Pasien
                </p>
            }
        >
            {!pasien ? (
                <Card>Pasien tidak ditemukan</Card>
            ) : (
                <>
                    <Head title="Pasien Rujukan List" />
                    <Row>
                        <Col span={24}>
                            <PasienRujukanDetailProfile pasien={pasien} />
                        </Col>
                    </Row>

                    <Row>
                        <Col span={12}>
                            <PasienRujukanDetailResume pasien={pasien} />
                        </Col>
                        <Col span={12}>
                            <PasienRujukanDetailHasilLab pasien={pasien} />
                        </Col>
                    </Row>

                    <Row>
                        <Col span={12}>
                            <PasienRujukanDetailDiagnosaList pasien={pasien} />
                        </Col>
                        <Col span={12}>
                            <PasienRujukanDetailProcedureList pasien={pasien} />
                        </Col>
                    </Row>

                    <Row>
                        <Col span={12}>
                            <PasienRujukanDetailAmnanesaCatatan
                                pasien={pasien}
                            />
                            <PasienRujukanDetailCaraMasukPulang
                                pasien={pasien}
                                kode_reg={kode_reg}
                                pasienLoading={pasienLoading}
                                reFetchPasien={reFetchPasien}
                            />
                        </Col>
                        <Col span={12}>
                            <PasienRujukanDetailSEP
                                pasien={pasien}
                                user={auth.user}
                            />
                        </Col>
                    </Row>
                </>
            )}
        </AuthenticatedLayout>
    );
}
