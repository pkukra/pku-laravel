import React, { useState, useEffect } from "react";
import { Row, Col, Button, Modal, notification, Divider } from "antd";
import axios from "axios";

import DiagnosaListINACBG from "./DiagnosaListINACBG";
import ProcedureListINACBG from "./ProcedureListINACBG";

function Index({ pasien }) {
    const [shouldRefetchData, setShouldReFetch] = useState(false);
    const [loadingFetchGroupData, setLoadingFetchGroupData] = useState(false);
    const [inacbgGroupData, setInacbgGroupData] = useState(null);

    const [isDiagnosaHasErr, setDiagnosaHasErr] = useState(true); // ambil diagnosa error dari child komponen DiagnosaListINACBG
    const [isProcedureHasErr, setProcedureHasErr] = useState(true); // ambil diagnosa error dari child komponen ProcedureListINACBG

    const [modalImportAndBridgeOpen, setModalImportAndBridgeOpen] =
        useState(false);
    const [importAndBridgeLoading, setImportAndBridgeLoading] = useState(false);

    const handleImportAndBridgingData = async () => {
        setImportAndBridgeLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_import_idrg_to_inacbg", {
                    no_sep: pasien?.FMNOSEP,
                })
            );

            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code === 400) {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            return notification.success({
                placement: "topRight",
                message: "Sukses!",
                description: "sukses mengimport data dari idrg",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setShouldReFetch((prev) => !prev);
            setImportAndBridgeLoading(false);
            setModalImportAndBridgeOpen(false);
        }
    };

    const eklaim_group_data = JSON.parse(
        inacbgGroupData?.response_eklaim || "{}"
    );

    return (
        <>
            <p>
                <strong>inaCBG</strong>
            </p>
            <Row gutter={[5, 5]}>
                <Col span={12}>
                    <DiagnosaListINACBG
                        pasien={pasien}
                        trigerFetchDiagnosa={shouldRefetchData}
                        setDiagnosaHasErr={setDiagnosaHasErr}
                    />
                </Col>
                <Col span={12}>
                    <ProcedureListINACBG
                        pasien={pasien}
                        trigerFetchProcedure={shouldRefetchData}
                        setProcedureHasErr={setProcedureHasErr}
                    />
                </Col>
            </Row>
            <Row gutter={[5, 5]}>
                <Col span={12}></Col>
                <Col span={12}>
                    <Divider> Hasil Grouping inaCBG </Divider>
                    {loadingFetchGroupData ? (
                        <p>Loading...</p>
                    ) : (
                        <table
                            style={{
                                borderCollapse: "collapse",
                                width: "100%",
                                margin: 10,
                            }}
                        >
                            <tbody>
                                <tr>
                                    <td style={{ width: "15%" }}>
                                        Status Grouping
                                    </td>
                                    <td>
                                        {inacbgGroupData ? (
                                            <strong>Sudah Grouping</strong>
                                        ) : (
                                            <strong>Belum Grouping</strong>
                                        )}
                                    </td>
                                </tr>
                                <tr>
                                    <td style={{ width: "15%" }}>
                                        Status Final
                                    </td>
                                    <td>Status Final</td>
                                </tr>
                                <tr>
                                    <td style={{ width: "15%" }}>MDC Number</td>
                                    <td>{eklaim_group_data?.mdc_number}</td>
                                </tr>
                                <tr>
                                    <td
                                        style={{
                                            verticalAlign: "top",
                                        }}
                                    >
                                        MDC Description
                                    </td>
                                    <td
                                        style={{
                                            verticalAlign: "top",
                                        }}
                                    >
                                        {eklaim_group_data?.mdc_description}
                                    </td>
                                </tr>
                                <tr>
                                    <td>DRG Code</td>
                                    <td>{eklaim_group_data?.drg_code}</td>
                                </tr>
                                <tr>
                                    <td>DRG Description</td>
                                    <td>
                                        {eklaim_group_data?.drg_description}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    )}

                    <Divider />
                    <Button
                        disabled={pasien?.FMNOSEP ? false : true}
                        type="primary"
                        onClick={() => {
                            setModalImportAndBridgeOpen(true);
                            return;
                        }}
                        style={{
                            marginRight: 5,
                            backgroundColor: " #33cc33",
                        }}
                    >
                        Import inaCBG
                    </Button>

                    <Button
                        disabled={isDiagnosaHasErr || isProcedureHasErr}
                        type="primary"
                        onClick={() => {
                            return;
                        }}
                        style={{
                            marginRight: 5,
                        }}
                    >
                        Grouping
                    </Button>
                </Col>
            </Row>

            <Modal
                closable={false}
                open={modalImportAndBridgeOpen}
                title="Import dan bridging data dengan SEP tersebut:"
                onCancel={() => setModalImportAndBridgeOpen(false)}
                footer={[
                    <Button
                        disabled={importAndBridgeLoading}
                        loading={importAndBridgeLoading}
                        key="back"
                        onClick={() => setModalImportAndBridgeOpen(false)}
                    >
                        Cancel
                    </Button>,
                    <Button
                        loading={importAndBridgeLoading}
                        disabled={importAndBridgeLoading}
                        key="submit"
                        type="primary"
                        onClick={() => handleImportAndBridgingData()}
                        style={{ backgroundColor: " #33cc33" }}
                    >
                        Ok, Import & Bridging Data
                    </Button>,
                ]}
            >
                <br />
                {pasien?.FMNOSEP ? (
                    <div>
                        <strong>Nomor SEP:</strong> {pasien?.FMNOSEP}
                    </div>
                ) : (
                    <strong>Belum ada data SEP</strong>
                )}

                <p>
                    Proses ini mengakibatkan diagnosa & prosedure yang tersimpan di inaCBG
                    terganti dengan data import dari idrg. Apakah setuju untuk melanjutkan?
                </p>
            </Modal>
        </>
    );
}

export default Index;
