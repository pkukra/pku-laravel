import React, { useState } from "react";
import { Head } from "@inertiajs/react";
import { Col, Row } from "antd";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PasienRujukanDetailProfile from "./PasienRujukanDetailProfile";
import PasienRujukanDetailDiagnosaList from "./PasienRujukanDetailDiagnosaList";
import PasienRujukanDetailProcedureList from "./PasienRujukanDetailProcedureList";
import PasienRujukanDetailAmnanesaCatatan from "./PasienRujukanDetailAmnanesaCatatan";
import PasienRujukanDetailBridging from "./PasienRujukanDetailBridging";
import PasienRujukanDetailSEP from "./PasienRujukanDetailSEP";

export default function PasienRujukanDetail({ auth, pasien }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <p className="font-semibold text-lg text-gray-800 leading-tight">
                    Detail Kunjungan Pasien
                </p>
            }
        >
            <Head title="Pasien Rujukan List" />
            <PasienRujukanDetailProfile pasien={pasien} />

            <Row>
                <Col span={12} style={{ padding: 2 }}>
                    <PasienRujukanDetailDiagnosaList pasien={pasien} />
                </Col>
                <Col span={12} style={{ padding: 2 }}>
                    <PasienRujukanDetailProcedureList pasien={pasien} />
                </Col>
            </Row>

            <Row>
                <Col span={12} style={{ padding: 2 }}>
                    <PasienRujukanDetailAmnanesaCatatan pasien={pasien} />
                </Col>
                <Col span={12} style={{ padding: 2 }}>
                    <PasienRujukanDetailSEP
                        pasien={pasien}
                        user={auth.user}
                        // setNoSep={setNoSep}
                        // noSep={noSep}
                    />
                    {/* <PasienRujukanDetailBridging
                        pasien={pasien}
                        user={auth.user}
                        noSep={noSep}
                    /> */}
                </Col>
            </Row>
        </AuthenticatedLayout>
    );
}
