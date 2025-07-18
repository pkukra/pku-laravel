import { Head } from "@inertiajs/react";
import { Col, Row, Card, Alert } from "antd";
import { useState } from "react";
import axios from "axios";

import config from "../../../config";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PasienInapDetailProfile from "./PasienInapDetailProfile";
import PasienInapDetailDiagnosaList from "./PasienInapDetailDiagnosaList";
import PasienInapDetailProcedureList from "./PasienInapDetailProcedureList";
import PasienInapDetailSEP from "./PasienInapDetailSEP";
import PasienInapDetailAssesmenAwal from "./PasienInapDetailAssesmenAwal";
import PasienInapDetailBerkasPenunjang from "./PasienInapDetailBerkasPenunjang";
import PasienInapDetailPerawatan from "./PasienInapDetailPerawatan";
import EKlaim from "../Eklaim";

function PasienInapDetail({ auth, pasien: initialPasien, kode_reg }) {
    const [pasien, setPasien] = useState(initialPasien);
    const [pasienLoading, setPasienLoading] = useState(false);
    const [disableINACBG, setDisableINACBG] = useState(true);

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

    let customer_id = pasien?.FRPCUSTOMER_ID;
    let pasien_id = pasien?.FRPPASIEN_ID;

    if (pasien?.JENIS_RAWAT == "ranap") {
        customer_id = pasien?.PRWIKD_CUSTOMER;
        kode_reg = pasien?.FTNO_TRANSAKSI;
        pasien_id = pasien?.FTKD_PASIEN;
    }

    const disableInvalidSEP = () => {
        if (
            ["X002", "X003"].includes(customer_id) &&
            pasien?.IS_SEP_VALID == false
        ) {
            return true;
        }
        return false;
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
                            <PasienInapDetailAssesmenAwal pasien={pasien} />
                        </Col>
                        <Col span={12}>
                            <PasienInapDetailBerkasPenunjang pasien={pasien} />
                        </Col>

                        {config.is_idrg ? (
                            <Col span={24}>
                                {disableInvalidSEP() && (
                                    <Alert
                                        message="Warning: Invalid SEP"
                                        type="error"
                                        banner
                                        closable
                                    />
                                )}
                                <EKlaim
                                    pasien={pasien}
                                    setDisableINACBG={setDisableINACBG}
                                    disableINACBG={disableINACBG}
                                    reFetchPasien={reFetchPasien}
                                />
                            </Col>
                        ) : (
                            <>
                                <Col span={12}>
                                    <PasienInapDetailDiagnosaList
                                        pasien={pasien}
                                    />
                                </Col>
                                <Col span={12}>
                                    <PasienInapDetailProcedureList
                                        pasien={pasien}
                                    />
                                </Col>
                            </>
                        )}

                        <Col span={12}>
                            <PasienInapDetailPerawatan
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
                                reFetchPasien={reFetchPasien}
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
