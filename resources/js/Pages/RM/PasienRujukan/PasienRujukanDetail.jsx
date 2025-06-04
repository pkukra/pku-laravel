import { Head } from "@inertiajs/react";
import { Col, Row, Card, Tabs, Spin } from "antd";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PasienRujukanDetailProfile from "./PasienRujukanDetailProfile";
import PasienRujukanDetailAmnanesaCatatan from "./PasienRujukanDetailAmnanesaCatatan";
import PasienRujukanDetailSEP from "./PasienRujukanDetailSEP";
import PasienRujukanDetailResume from "./PasienRujukanDetailResume";
import PasienRujukanDetailAssesmenIGD from "./PasienRujukanDetailAssesmenIGD";
import PasienRujukanDetailHasilLab from "./PasienRujukanDetailHasilLab";
import PasienRujukanDetailCaraMasukPulang from "./PasienRujukanDetailCaraMasukPulang";

import IndexTabIDRG from "../IDRG/IndexTabIDRG";
import IndexTabINACBG from "../INACBG/IndexTabINACBG";

import { useState, useEffect } from "react";
import axios from "axios";

function PasienRujukanDetail({ auth, pasien: initialPasien, kode_reg }) {
    const [pasien, setPasien] = useState(initialPasien);
    const [golbalSEP, setGolbalSEP] = useState(null);

    const [loadingRaber, setLoadingRaber] = useState(true);
    const [listRaber, setListRaber] = useState([]);

    const [pasienLoading, setPasienLoading] = useState(false);
    const [disableINACBG, setDisableINACBG] = useState(true);

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

    const fetchAllRelatedRaber = () => {
        setLoadingRaber(true);
        axios
            .get(
                route("rm.pasien-rujukan.list_all_raber", { no_sep: golbalSEP })
            )
            .then((response) => {
                setListRaber(response?.data || []);
                setLoadingRaber(false);
            })
            .catch((error) =>
                console.error("Error fetchAllRelatedRaber:", error)
            )
            .finally(() => setLoadingRaber(false));
    };

    useEffect(() => {
        if (pasien?.FRPUNIT != "PK011") {
            if (golbalSEP) {
                fetchAllRelatedRaber();
            }
        } else {
            setLoadingRaber(false);
        }
    }, [golbalSEP]);

    const menu = [
        {
            label: "IDRG",
            key: "1",
            children: (
                <IndexTabIDRG
                    pasien={pasien}
                    golbalSEP={golbalSEP}
                    setDisableINACBG={setDisableINACBG}
                />
            ),
        },
        {
            label: "INACBG",
            key: "2",
            children: <IndexTabINACBG golbalSEP={golbalSEP} pasien={pasien} />,
            disabled: disableINACBG,
        },
    ];

    const itemTabDokter = listRaber.map((item, index) => ({
        label: item?.FMDDOKTERN,
        key: String(index + 1), // gunakan key unik untuk setiap tab
        children: (
            <>
                <Row gutter={[5, 5]}>
                    <Col span={12}>
                        <PasienRujukanDetailResume
                            pasien={pasien}
                            dataTransaksi={item}
                        />
                    </Col>
                    <Col span={12}>
                        <PasienRujukanDetailHasilLab
                            pasien={pasien}
                            dataTransaksi={item}
                        />
                    </Col>
                </Row>
            </>
        ),
    }));

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

                        <Col span={24}>
                            {pasien?.FRPUNIT === "PK011" ? (
                                <Row gutter={[5, 5]}>
                                    <Col span={12}>
                                        <PasienRujukanDetailAssesmenIGD
                                            pasien={pasien}
                                        />
                                    </Col>
                                    <Col span={12}>
                                        <PasienRujukanDetailHasilLab
                                            pasien={pasien}
                                            dataTransaksi={pasien}
                                        />
                                    </Col>
                                </Row>
                            ) : pasien?.FRPCUSTOMER_ID != "X002" &&
                              pasien?.FRPCUSTOMER_ID != "X003" ? (
                                <Card>
                                    <Row gutter={[5, 5]}>
                                        <Col span={12}>
                                            <PasienRujukanDetailResume
                                                pasien={pasien}
                                                dataTransaksi={pasien}
                                            />
                                        </Col>
                                        <Col span={12}>
                                            <PasienRujukanDetailHasilLab
                                                pasien={pasien}
                                                dataTransaksi={pasien}
                                            />
                                        </Col>
                                    </Row>
                                </Card>
                            ) : (
                                // else default
                                <Card loading={loadingRaber}>
                                    <Tabs
                                        defaultActiveKey="1"
                                        type="card"
                                        size={"small"}
                                        style={{ marginBottom: 32 }}
                                        items={itemTabDokter}
                                    />
                                </Card>
                            )}
                        </Col>
                        <Col span={24}>
                            <Card>
                                <Tabs
                                    defaultActiveKey="1"
                                    type="card"
                                    size={"small"}
                                    style={{ marginBottom: 32 }}
                                    items={menu}
                                />
                            </Card>
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
                                setGolbalSEP={setGolbalSEP}
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
