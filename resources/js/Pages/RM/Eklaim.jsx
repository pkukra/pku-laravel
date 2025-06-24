import React, { useState, useEffect } from "react";
import {
    Col,
    Row,
    Card,
    Tabs,
    Divider,
    Button,
    Modal,
    notification,
} from "antd";

import IndexTabIDRG from "./IDRG/IndexTabIDRG";
import IndexTabINACBG from "./INACBG/IndexTabINACBG";

import axios from "axios";

function EKlaim({ pasien, setDisableINACBG, disableINACBG, reFetchPasien }) {
    const [loadingFetchIdrgData, setLoadingFetchIdrgData] = useState(false);
    const [idrgGroupData, setIdrgGroupData] = useState(null);

    const [modalFinalOpen, setModalFinalOpen] = useState(false);
    const [finalLoading, setFinalLoading] = useState(false);

    const [modalReeditOpen, setModalReeditOpen] = useState(false);
    const [reeditLoading, setReeditLoading] = useState(false);

    const [loadingFetchInacbgData, setLoadingFetchInacbgData] = useState(false);
    const [inacbgGroupData, setInacbgGroupData] = useState(null);

    const [modalKirimOnlineOpen, setModalKirimOnlineOpen] = useState(false);
    const [kirimOnlineLoading, setKirimOnlineLoading] = useState(false);

    const [EkliamData, SetEklaimData] = useState(null);

    let no_sep = pasien?.FMNOSEP || null;
    if (pasien?.JENIS_RAWAT == "ranap") {
        no_sep = pasien?.FMNOSEP || null;
    }

    const fetchIDRGData = async () => {
        setLoadingFetchIdrgData(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_idrg_group_data", {
                    no_sep: no_sep,
                })
            )
            .then((response) => {
                setIdrgGroupData(response?.data?.data || null);
                setLoadingFetchIdrgData(false);
            })
            .catch((error) => {
                setLoadingFetchIdrgData(false);
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingFetchIdrgData(false);
            });
    };

    const fetchEKLAIMata = async () => {
        setLoadingFetchInacbgData(true);
        axios
            .get(
                route("rm.bridging_get_claim_data", {
                    no_sep: no_sep,
                })
            )
            .then((response) => {
                if (response?.data?.response?.metadata?.code != 200) {
                    SetEklaimData(null);
                    return;
                }
                SetEklaimData(response?.data?.response?.response);
                return;
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingFetchInacbgData(false);
            });
        return;
    };

    const fetchINACBGData = async () => {
        setLoadingFetchInacbgData(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_inacbg_group_data", {
                    no_sep: no_sep,
                })
            )
            .then((response) => {
                setInacbgGroupData(response?.data || null);
                setLoadingFetchInacbgData(false);
            })
            .catch((error) => {
                setLoadingFetchInacbgData(false);
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingFetchInacbgData(false);
            });
    };

    const handleFinalData = async () => {
        setFinalLoading(true);
        let routeName = "rm.pasien-rujukan.bridging_final_klaim";
        if (pasien?.JENIS_RAWAT == "ranap") {
            routeName = "rm.pasien-inap.bridging_final_klaim";
        }
        try {
            const response = await axios.post(
                route(routeName, {
                    no_sep: no_sep,
                })
            );

            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code != 200) {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            notification.success({
                placement: "topRight",
                message: "Sukses!",
                description: "Sukses final data Klaim",
            });

            return;
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            reFetchPasien()
            setFinalLoading(false);
            setModalFinalOpen(false);
            fetchINACBGData();
            fetchEKLAIMata();
        }
        return;
    };

    const handleReeditKlaim = async () => {
        setReeditLoading(true);
        let routeName = "rm.pasien-rujukan.bridging_reedit_klaim";
        if (pasien?.JENIS_RAWAT == "ranap") {
            routeName = "rm.pasien-inap.bridging_reedit_klaim";
        }
        try {
            const response = await axios.post(
                route(routeName, {
                    no_sep: no_sep,
                })
            );

            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code != 200) {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            notification.success({
                placement: "topRight",
                message: "Sukses!",
                description: "Sukses Edit Ulang Klaim",
            });
            return;
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            reFetchPasien()
            setReeditLoading(false);
            setModalReeditOpen(false);
            fetchINACBGData();
            fetchEKLAIMata();
        }
        return;
    };

    const handleKirimKlaimIndividual = async () => {
        setKirimOnlineLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_send_invidual_klaim", {
                    no_sep: no_sep,
                })
            );

            if (response?.data?.status == "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code != 200) {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            notification.success({
                placement: "topRight",
                message: "Sukses!",
                description: "Sukses Edit Ulang Klaim",
            });
            return;
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setModalKirimOnlineOpen(false);
            setKirimOnlineLoading(false);
            fetchINACBGData();
            fetchEKLAIMata();
        }
        return;
    };

    const hadleCetakKlaim = () => {
        const url = route("rm.bridging_cetak_klaim", {
            no_sep: no_sep,
        });

        window.open(url, "_blank");
    };

    useEffect(() => {
        if (no_sep) {
            fetchINACBGData();
            fetchEKLAIMata();
        }
    }, [pasien]);

    const menu = [
        {
            label: "IDRG",
            key: "1",
            children: (
                <IndexTabIDRG
                    fetchIDRGData={fetchIDRGData}
                    fetchINACBGData={fetchINACBGData}
                    loadingFetchIdrgData={loadingFetchIdrgData}
                    idrgGroupData={idrgGroupData}
                    pasien={pasien}
                    setDisableINACBG={setDisableINACBG}
                    isKlaimFinal={inacbgGroupData?.is_final_claim}
                />
            ),
        },
        {
            label: "INACBG",
            key: "2",
            children: (
                <IndexTabINACBG
                    pasien={pasien}
                    inacbgGroupData={inacbgGroupData}
                    fetchINACBGData={fetchINACBGData}
                    loadingFetchInacbgData={loadingFetchInacbgData}
                    isKlaimFinal={inacbgGroupData?.is_final_claim}
                />
            ),
            disabled: disableINACBG,
        },
    ];

    const disableFinalButton = () => {
        if (idrgGroupData?.is_final != 1 || inacbgGroupData?.is_final != 1) {
            // jika salah satu idrg atau inacbg belum final maka tidak bisa di final klaim
            return true;
        }
    };

    return (
        <>
            <Card>
                <Row gutter={[5, 5]}>
                    <Col span={24}>
                        <Tabs
                            onChange={(key) => {
                                if (key == 1) {
                                    fetchIDRGData();
                                } else {
                                    fetchINACBGData();
                                }
                            }}
                            defaultActiveKey="1"
                            type="card"
                            size={"small"}
                            style={{ marginBottom: 32 }}
                            items={menu}
                        />
                    </Col>
                </Row>
                <Row gutter={[5, 5]}>
                    <Col span={12}></Col>
                    <Col span={12}>
                        <Divider> Final Klaim </Divider>
                        {loadingFetchInacbgData ? (
                            <p>Loading data...</p>
                        ) : (
                            <>
                                <p>
                                    Status Final EKLAIM :{" "}
                                    {inacbgGroupData?.is_final_claim == 1 ? (
                                        <strong>Sudah Final</strong>
                                    ) : (
                                        <strong>Belum Final</strong>
                                    )}
                                </p>
                                <p>
                                    Status Terkirim Ke DC Kemenkes :{" "}
                                    <strong>
                                        {
                                            EkliamData?.data
                                                ?.kemenkes_dc_status_cd
                                        }
                                    </strong>
                                </p>
                                <p>
                                    Status Terkirim Ke DC BPJS :{" "}
                                    <strong>
                                        {EkliamData?.data?.bpjs_dc_status_cd}
                                    </strong>
                                </p>
                            </>
                        )}
                        {inacbgGroupData?.is_final_claim != 1 ? (
                            <Button
                                disabled={disableFinalButton() || finalLoading}
                                danger
                                type="primary"
                                onClick={() => {
                                    setModalFinalOpen(true);
                                    return;
                                }}
                                style={{
                                    marginRight: 5,
                                }}
                            >
                                Final Klaim
                            </Button>
                        ) : (
                            <Button
                                danger
                                disabled={disableFinalButton() || reeditLoading}
                                onClick={() => {
                                    setModalReeditOpen(true);
                                    return;
                                }}
                                style={{
                                    marginRight: 5,
                                }}
                            >
                                Edit Ulang Klaim
                            </Button>
                        )}

                        <Button
                            disabled={
                                inacbgGroupData?.is_final_claim != 1 ||
                                kirimOnlineLoading
                            }
                            loading={kirimOnlineLoading}
                            danger
                            type="primary"
                            onClick={() => {
                                setModalKirimOnlineOpen(true);
                                return;
                            }}
                            style={{
                                marginRight: 5,
                            }}
                        >
                            Kirim Klaim Online
                        </Button>
                        <Button
                            disabled={inacbgGroupData?.is_final_claim != 1}
                            onClick={hadleCetakKlaim}
                            style={{
                                marginRight: 5,
                            }}
                        >
                            Cetak Klaim
                        </Button>
                    </Col>
                </Row>
            </Card>

            <Modal
                open={modalFinalOpen}
                title="Final Klaim...?"
                onCancel={() => setModalFinalOpen(false)}
                footer={[
                    <Button
                        key="back"
                        onClick={() => setModalFinalOpen(false)}
                        loading={finalLoading}
                    >
                        Cancel
                    </Button>,
                    <Button
                        disabled={pasien?.FMNOSEP !== null ? false : true}
                        key="submit"
                        type="primary"
                        danger
                        loading={finalLoading}
                        onClick={() => handleFinalData()}
                    >
                        Ok, Final Klaim
                    </Button>,
                ]}
            >
                {pasien?.FMNOSEP ? (
                    <div>
                        <p>
                            <strong>Nomor SEP:</strong> {pasien?.FMNOSEP}
                        </p>
                    </div>
                ) : (
                    <p>
                        <strong>Belum ada data SEP</strong>
                    </p>
                )}
            </Modal>

            <Modal
                open={modalReeditOpen}
                title="Edit Ulang Klaim...?"
                onCancel={() => setModalReeditOpen(false)}
                footer={[
                    <Button
                        key="Editback"
                        onClick={() => setModalReeditOpen(false)}
                        loading={reeditLoading}
                    >
                        Cancel
                    </Button>,
                    <Button
                        dashed
                        danger
                        disabled={pasien?.FMNOSEP !== null ? false : true}
                        key="Editsubmit"
                        loading={reeditLoading}
                        onClick={() => handleReeditKlaim()}
                    >
                        Ok, Edit Ulang Klaim
                    </Button>,
                ]}
            >
                {pasien?.FMNOSEP ? (
                    <div>
                        <p>
                            <strong>Nomor SEP:</strong> {pasien?.FMNOSEP}
                        </p>
                    </div>
                ) : (
                    <p>
                        <strong>Belum ada data SEP</strong>
                    </p>
                )}
            </Modal>

            <Modal
                open={modalKirimOnlineOpen}
                title="Kirim klaim ke data center Kemenkes...?"
                onCancel={() => setModalKirimOnlineOpen(false)}
                footer={[
                    <Button
                        key="back"
                        onClick={() => setModalKirimOnlineOpen(false)}
                        loading={kirimOnlineLoading}
                    >
                        Cancel
                    </Button>,
                    <Button
                        dashed
                        danger
                        disabled={pasien?.FMNOSEP !== null ? false : true}
                        key="submit"
                        loading={kirimOnlineLoading}
                        onClick={() => {
                            handleKirimKlaimIndividual();
                            return;
                        }}
                    >
                        Ok, Kirim Klaim
                    </Button>,
                ]}
            >
                {pasien?.FMNOSEP ? (
                    <div>
                        <p>
                            <strong>Nomor SEP:</strong> {pasien?.FMNOSEP}
                        </p>
                    </div>
                ) : (
                    <p>
                        <strong>Belum ada data SEP</strong>
                    </p>
                )}
            </Modal>
        </>
    );
}

export default EKlaim;
