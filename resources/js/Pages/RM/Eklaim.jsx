import React, { useState, useEffect } from "react";
import { Head } from "@inertiajs/react";
import { Col, Row, Card, Tabs, Divider, Button } from "antd";

import IndexTabIDRG from "./IDRG/IndexTabIDRG";
import IndexTabINACBG from "./INACBG/IndexTabINACBG";

import axios from "axios";

function EKlaim({ pasien, setDisableINACBG, disableINACBG }) {
    const [loadingFetchIdrgData, setLoadingFetchIdrgData] = useState(false);
    const [idrgGroupData, setIdrgGroupData] = useState(null);

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
                />
            ),
            disabled: disableINACBG,
        },
    ];

    console.log("idrgGroupData is_final", );
    console.log("inacbgGroupData is_final", inacbgGroupData?.is_final);
    

    const disableFinalButton = () => {
        if(idrgGroupData?.is_final != 1 || inacbgGroupData?.is_final != 1) {
            // jika salah satu idrg atau inacbg belum final maka tidak bisa di final klaim
            return true;
        }
    }

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
                        <Button
                            disabled={disableFinalButton()}
                            danger
                            type="primary"
                            onClick={() => {
                                return;
                            }}
                            style={{
                                marginRight: 5,
                            }}
                        >
                            Final Klaim
                        </Button>
                    </Col>
                </Row>
            </Card>
        </>
    );
}

export default EKlaim;