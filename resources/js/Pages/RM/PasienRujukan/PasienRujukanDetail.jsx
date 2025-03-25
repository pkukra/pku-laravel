import { Head } from "@inertiajs/react";
import { Col, Row, Card } from "antd";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PasienRujukanDetailProfile from "./PasienRujukanDetailProfile";
import PasienRujukanDetailDiagnosaList from "./PasienRujukanDetailDiagnosaList";
import PasienRujukanDetailProcedureList from "./PasienRujukanDetailProcedureList";
import PasienRujukanDetailAmnanesaCatatan from "./PasienRujukanDetailAmnanesaCatatan";
import PasienRujukanDetailSEP from "./PasienRujukanDetailSEP";
import PasienRujukanDetailResume from "./PasienRujukanDetailResume";
import PasienRujukanDetailAssesmenIGD from "./PasienRujukanDetailAssesmenIGD";
import PasienRujukanDetailHasilLab from "./PasienRujukanDetailHasilLab";
import PasienRujukanDetailCaraMasukPulang from "./PasienRujukanDetailCaraMasukPulang";
import { useState } from "react";
import axios from "axios";

function PasienRujukanDetail({ auth, pasien: initialPasien, kode_reg }) {
    const [pasien, setPasien] = useState(initialPasien);
    const [pasienLoading, setPasienLoading] = useState(false);

    const reFetchPasien = () => {
        setPasienLoading(true);
        axios
            .get(route("rm.pasien-rujukan.detail_data", { kode_reg }))
            .then((response) => setPasien(response?.data?.pasien))
            .catch((error) =>
                console.error("Error fetching data pasien:", error)
            )
            .finally(() => setPasienLoading(false));
    };

    return (
        <>
            <Head title="Detail Kunjungan Pasien Rajal" />

            <div className="py-12">
                {!pasien ? (
                    <Card>Pasien tidak ditemukan</Card>
                ) : (
                    <Row gutter={[5, 5]}>
                        <Col span={24}>
                            <PasienRujukanDetailProfile pasien={pasien} />
                        </Col>

                        <Col span={12}>
                            {pasien?.FRPUNIT == "PK011" ? (
                                <PasienRujukanDetailAssesmenIGD
                                    pasien={pasien}
                                />
                            ) : (
                                <PasienRujukanDetailResume pasien={pasien} />
                            )}
                        </Col>
                        <Col span={12}>
                            <PasienRujukanDetailHasilLab pasien={pasien} />
                        </Col>

                        <Col span={12}>
                            <PasienRujukanDetailDiagnosaList pasien={pasien} />
                        </Col>
                        <Col span={12}>
                            <PasienRujukanDetailProcedureList pasien={pasien} />
                        </Col>

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
                )}
            </div>
        </>
    );
}

PasienRujukanDetail.layout = (page) => <AuthenticatedLayout children={page} />;

export default PasienRujukanDetail;
