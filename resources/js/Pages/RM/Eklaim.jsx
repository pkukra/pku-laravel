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

function EKlaim({ pasien, setDisableINACBG, disableINACBG }) {
    const [loadingFetchIdrgData, setLoadingFetchIdrgData] = useState(false);
    const [idrgGroupData, setIdrgGroupData] = useState(null);

    const [modalFinalOpen, setModalFinalOpen] = useState(false);
    const [finalLoading, setFinalLoading] = useState(false);

    const [modalReeditOpen, setModalReeditOpen] = useState(false);
    const [reeditLoading, setReeditLoading] = useState(false);

    const [loadingFetchInacbgData, setLoadingFetchInacbgData] = useState(false);
    const [inacbgGroupData, setInacbgGroupData] = useState(null);

    const fetchIDRGData = async () => {
        setLoadingFetchIdrgData(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_idrg_group_data", {
                    no_sep: pasien?.FMNOSEP,
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

    const fetchINACBGData = async () => {
        setLoadingFetchInacbgData(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_inacbg_group_data", {
                    no_sep: pasien?.FMNOSEP,
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
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_final_klaim", {
                    no_sep: pasien?.FMNOSEP,
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
            setFinalLoading(false);
            setModalFinalOpen(false);
            fetchINACBGData();
        }
        return;
    };

    const handleReeditKlaim = async () => {
        setReeditLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_reedit_klaim", {
                    no_sep: pasien?.FMNOSEP,
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
            setReeditLoading(false);
            setModalReeditOpen(false);
            fetchINACBGData();
        }
        return;
    };

    useEffect(() => {
        if (pasien?.FMNOSEP) {
            fetchINACBGData();
        }
    }, [pasien?.FMNOSEP]);

    const menu = [
        {
            label: "IDRG",
            key: "1",
            children: (
                <IndexTabIDRG
                    fetchIDRGData={fetchIDRGData}
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
                            <p>
                                Status Final EKLAIM :{" "}
                                {inacbgGroupData?.is_final_claim ? (
                                    <strong>Sudah Final</strong>
                                ) : (
                                    <strong>Belum Final</strong>
                                )}
                            </p>
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
                                dashed
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
                    </Col>
                </Row>
            </Card>

            <Modal
                open={modalFinalOpen}
                title="Final Klaim"
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
                title="Edit Ulang Klaim"
                onCancel={() => setModalReeditOpen(false)}
                footer={[
                    <Button
                        key="back"
                        onClick={() => setModalReeditOpen(false)}
                        loading={reeditLoading}
                    >
                        Cancel
                    </Button>,
                    <Button
                        dashed
                        danger
                        disabled={pasien?.FMNOSEP !== null ? false : true}
                        key="submit"
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
        </>
    );
}

export default EKlaim;
