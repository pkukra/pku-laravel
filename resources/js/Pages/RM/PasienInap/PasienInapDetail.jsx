import { Head } from "@inertiajs/react";
import { Col, Row, Card } from "antd";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PasienInapDetailProfile from "./PasienInapDetailProfile";
import PasienInapDetailDiagnosaList from "./PasienInapDetailDiagnosaList";
import PasienInapDetailProcedureList from "./PasienInapDetailProcedureList";
import PasienInapDetailSEP from "./PasienInapDetailSEP";
import PasienInapDetailResume from "./PasienInapDetailResume";
import PasienInapDetailHasilLab from "./PasienInapDetailHasilLab";
import PasienInapDetailCaraMasukPulang from "./PasienInapDetailCaraMasukPulang";
import { useState } from "react";
import axios from "axios";

function PasienInapDetail({ auth, pasien: initialPasien, kode_reg }) {
    const [pasien, setPasien] = useState(initialPasien);
    const [pasienLoading, setPasienLoading] = useState(false);

    const reFetchPasien = () => {
        setPasienLoading(true);
        axios
            .get(route("rm.pasien-inap.detail_data", { kode_reg }))
            .then((response) => setPasien(response?.data?.pasien))
            .catch((error) =>
                console.error("Error fetching data pasien:", error)
            )
            .finally(() => setPasienLoading(false));
    };

    return (
        <>
            <Head title="Detail Kunjungan Pasien Ranap" />

            <div className="py-12">
                {!pasien ? (
                    <Card>Pasien tidak ditemukan</Card>
                ) : (
                    <Row gutter={[5, 5]}>
                        <Col span={24}>
                            <PasienInapDetailProfile pasien={pasien} />
                        </Col>

                        <Col span={12}>
                            <PasienInapDetailResume pasien={pasien} />
                        </Col>
                        <Col span={12}>
                            <PasienInapDetailHasilLab pasien={pasien} />
                        </Col>

                        <Col span={12}>
                            <PasienInapDetailDiagnosaList pasien={pasien} />
                        </Col>
                        <Col span={12}>
                            <PasienInapDetailProcedureList pasien={pasien} />
                        </Col>

                        <Col span={12}>
                            <PasienInapDetailCaraMasukPulang
                                pasien={pasien}
                                kode_reg={kode_reg}
                                pasienLoading={pasienLoading}
                                reFetchPasien={reFetchPasien}
                            />
                        </Col>
                        <Col span={12}>
                            <PasienInapDetailSEP
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

PasienInapDetail.layout = (page) => <AuthenticatedLayout children={page} />;

export default PasienInapDetail;
