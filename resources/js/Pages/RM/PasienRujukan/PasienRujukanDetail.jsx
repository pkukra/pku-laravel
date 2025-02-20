import React from "react";
import { Head } from "@inertiajs/react";
import { Col, Row, Card } from "antd";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PasienRujukanDetailProfile from "./PasienRujukanDetailProfile";
import PasienRujukanDetailDiagnosaList from "./PasienRujukanDetailDiagnosaList";
import PasienRujukanDetailProcedureList from "./PasienRujukanDetailProcedureList";
import PasienRujukanDetailAmnanesaCatatan from "./PasienRujukanDetailAmnanesaCatatan";
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
            <Row>
                <Col span={24}>
                    <PasienRujukanDetailProfile pasien={pasien} />
                </Col>
            </Row>

            <Row>
                <Col span={12}>
                    <Card>hallo</Card>
                </Col>
                <Col span={12}></Col>
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
                    <PasienRujukanDetailAmnanesaCatatan pasien={pasien} />
                </Col>
                <Col span={12}>
                    <PasienRujukanDetailSEP pasien={pasien} user={auth.user} />
                </Col>
            </Row>
        </AuthenticatedLayout>
    );
}
