import React, { useState, useEffect } from "react";
import { Row, Col, Button, Modal, notification, Divider } from "antd";
import axios from "axios";

import DiagnosaListINACBG from "./DiagnosaListINACBG";
import ProcedureListINACBG from "./ProcedureListINACBG";

function Index({ pasien, golbalSEP, setDisableINACBG }) {
    return (
        <>
            <p>
                <strong>inaCBG</strong>
            </p>
            <Row gutter={[5, 5]}>
                <Col span={12}>
                    <DiagnosaListINACBG
                        pasien={pasien}
                    />
                </Col>
                <Col span={12}>
                    <ProcedureListINACBG
                        pasien={pasien}
                    />
                </Col>
            </Row>
            <Row gutter={[5, 5]}>
                <Col span={12}>
                    
                </Col>
                <Col span={12}>
                    halo
                </Col>
            </Row>
        </>
    );
}

export default Index;
