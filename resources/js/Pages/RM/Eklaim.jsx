import React, { useState, useEffect } from "react";
import { Head } from "@inertiajs/react";
import { Col, Row, Card, Tabs, Divider, Button } from "antd";

import IndexTabIDRG from "./IDRG/IndexTabIDRG";
import IndexTabINACBG from "./INACBG/IndexTabINACBG";

import axios from "axios";

function EKlaim({ pasien, setDisableINACBG, disableINACBG }) {
    const [loadingFetchInacbgData, setLoadingFetchInacbgData] = useState(false);
    const [inacbgGroupData, setInacbgGroupData] = useState(null);

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
                            disabled={inacbgGroupData?.is_final_claim == 1}
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
